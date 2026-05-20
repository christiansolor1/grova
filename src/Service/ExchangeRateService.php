<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Obtiene tasas de cambio (USD y EUR → HNL) de múltiples bancos hondureños.
 * Cachea 4 horas. Identifica qué banco da la mejor tasa de Compra para EUR.
 */
final class ExchangeRateService
{
    /** Misma fuente que bancatlan.hn y Atlántida Online (JSON público BASA en S3). */
    private const URL_JSON_ATLANTIDA = 'https://basa-static.s3.amazonaws.com/tasa-de-cambio.json';

    /** Clave de caché; subir versión al cambiar fuentes/parsers para no servir datos viejos. */
    private const CACHE_KEY = 'exchange_rates_multi_v6';

    /**
     * BAC Credomatic — JSON público (HN + LPS en arrays USD/EUR).
     * @see https://www.sucursalelectronica.com/ebac/common/GetExchangeRateInfo.go
     */
    private const URL_JSON_BAC = 'https://www.sucursalelectronica.com/ebac/common/GetExchangeRateInfo.go';

    /** Login BAC: tasas visibles en el header (mismo dato que el JSON, en HTML). */
    private const URL_LOGIN_BAC = 'https://www.sucursalelectronica.com/redir/showLogin.go';

    private const BANCOS_ESPERADOS = ['Atlántida', 'Ficohsa', 'Occidente'];

    private const ZONA_HONDURAS = 'America/Tegucigalpa';

    public static function claveCacheTasas(): string
    {
        return self::CACHE_KEY;
    }

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{
     *   banks: array<string, array{usd: float, usd_venta: float, eur: float, eur_venta: float, url: string}>,
     *   best_eur: string,
     *   best_usd: string,
     *   usd: float, usd_venta: float,
     *   eur: float, eur_venta: float,
     *   fecha: string,
     *   source: string
     * }
     */
    public function getRates(): array
    {
        $tasas = $this->leerTasasDesdeCache();
        if (!isset($tasas['banks']['Atlántida'])) {
            $this->cache->delete(self::CACHE_KEY);
            $tasas = $this->leerTasasDesdeCache();
        }

        return $tasas;
    }

    /** @return array{banks:array,best_eur:string,best_usd:string,usd:float,usd_venta:float,eur:float,eur_venta:float,fecha:string,source:string} */
    private function leerTasasDesdeCache(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $banks = [];

            $banks['Atlántida'] = $this->fetchAtlantida();
            $banks['Ficohsa']   = $this->fetchFicohsa();
            $banks['Occidente'] = $this->fetchOccidente();
            $banks['BAC']       = $this->fetchBAC();

            // Filtrar bancos que fallaron
            $banks = array_filter($banks, fn($b) => $b !== null);
            if (empty($banks)) {
                $item->expiresAfter(300);
                return $this->fallback();
            }

            $faltan = array_diff(self::BANCOS_ESPERADOS, array_keys($banks));
            // Respuesta incompleta (p. ej. Atlántida caída): reintentar pronto, no 4 h
            $item->expiresAfter(empty($faltan) ? 4 * 3600 : 300);

            // Mejor tasa EUR Compra (más lempiras por euro = mejor para quien recibe euros)
            $bestEur = array_key_first($banks);
            $bestUsd = array_key_first($banks);
            foreach ($banks as $name => $b) {
                if ($b['eur'] > $banks[$bestEur]['eur']) $bestEur = $name;
                if ($b['usd'] > $banks[$bestUsd]['usd']) $bestUsd = $name;
            }

            // Tasas del mejor banco para EUR (usadas en el cálculo del sueldo)
            $primary = $banks[$bestEur];

            return [
                'banks'     => $banks,
                'best_eur'  => $bestEur,
                'best_usd'  => $bestUsd,
                'usd'       => $primary['usd'],
                'usd_venta' => $primary['usd_venta'],
                'eur'       => $primary['eur'],
                'eur_venta' => $primary['eur_venta'],
                'fecha'     => (new \DateTimeImmutable('now', new \DateTimeZone(self::ZONA_HONDURAS)))->format('d/m/Y'),
                'source'    => $bestEur,
            ];
        });
    }

    public function toHnl(float $amount, string $currency = 'EUR'): float
    {
        $rates = $this->getRates();
        $rate  = strtoupper($currency) === 'USD' ? $rates['usd'] : $rates['eur'];
        return round($amount * $rate, 2);
    }

    // ── Parsers ──────────────────────────────────────────────────────────────

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function fetchAtlantida(): ?array
    {
        try {
            $json = $this->fetchJson(self::URL_JSON_ATLANTIDA);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || $data === []) {
                return null;
            }

            $hoy = (new \DateTimeImmutable('now', new \DateTimeZone(self::ZONA_HONDURAS)))->format('d-m-Y');
            $fecha = isset($data[$hoy]) ? $hoy : (string) array_key_last($data);
            $dia = $data[$fecha] ?? null;
            if (!is_array($dia) || empty($dia['USD']['compra']) || empty($dia['EUR']['compra'])) {
                return null;
            }

            return [
                'usd'       => (float) $dia['USD']['compra'],
                'usd_venta' => (float) ($dia['USD']['venta'] ?? $dia['USD']['compra']),
                'eur'       => (float) $dia['EUR']['compra'],
                'eur_venta' => (float) ($dia['EUR']['venta'] ?? $dia['EUR']['compra']),
                'url'       => 'aolweb.bancatlan.hn',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function fetchFicohsa(): ?array
    {
        try {
            $html = $this->fetch('https://www.ficohsa.hn/');
            // values-uno = USD, values-dos = EUR
            preg_match('/values-uno[^>]*>.*?buys-value[^>]*>\s*L\s*([\d.]+).*?sale-value[^>]*>\s*L\s*([\d.]+)/s', $html, $usd);
            preg_match('/values-dos[^>]*>.*?buys-value[^>]*>\s*L\s*([\d.]+).*?sale-value[^>]*>\s*L\s*([\d.]+)/s', $html, $eur);
            if (empty($usd[1]) || empty($eur[1])) return null;
            return ['usd' => (float)$usd[1], 'usd_venta' => (float)$usd[2], 'eur' => (float)$eur[1], 'eur_venta' => (float)$eur[2], 'url' => 'ficohsa.hn'];
        } catch (\Throwable) { return null; }
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function fetchOccidente(): ?array
    {
        try {
            $html = $this->fetch('https://www.bancodeoccidente.hn/enlinea/');
            // Solo tiene USD: "Compra: LPS. 26.6151" "Venta: LPS.26.7482"
            preg_match('/Compra[^>]*>\s*LPS\.?\s*([\d.]+)/i', $html, $c);
            preg_match('/Venta[^>]*>\s*LPS\.?\s*([\d.]+)/i', $html, $v);
            if (empty($c[1])) return null;
            return ['usd' => (float)$c[1], 'usd_venta' => (float)($v[1] ?? $c[1]), 'eur' => 0.0, 'eur_venta' => 0.0, 'url' => 'bancodeoccidente.hn'];
        } catch (\Throwable) { return null; }
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function fetchBAC(): ?array
    {
        return $this->fetchBACDesdeJson() ?? $this->fetchBACDesdeLoginHtml();
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function fetchBACDesdeJson(): ?array
    {
        try {
            $json = $this->fetchRespuestaBac(self::URL_JSON_BAC, 'application/json, */*', 8);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return null;
            }

            $usdHn = $this->buscarTasaBacHonduras($data['USD'] ?? []);
            $eurHn = $this->buscarTasaBacHonduras($data['EUR'] ?? []);
            if ($usdHn === null || (float) ($usdHn['buy'] ?? 0) <= 0) {
                return null;
            }

            return $this->armarTasasBac(
                (float) $usdHn['buy'],
                (float) ($usdHn['sell'] ?? $usdHn['buy']),
                $eurHn !== null ? (float) ($eurHn['buy'] ?? 0) : 0.0,
                $eurHn !== null ? (float) ($eurHn['sell'] ?? 0) : 0.0,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Tasas del header en showLogin.go: "Dólares: Compra L… Venta L…" / "Euros: …".
     * @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null
     */
    private function fetchBACDesdeLoginHtml(): ?array
    {
        try {
            $html = $this->fetchRespuestaBac(self::URL_LOGIN_BAC, 'text/html, */*', 12);
            if ($html === '' || !str_contains($html, 'Compra')) {
                return null;
            }

            preg_match(
                '/D[oó]lares?\s*:?\s*Compra\s*L?\s*([\d.]+)\s*Venta\s*L?\s*([\d.]+)/iu',
                $html,
                $usd,
            );
            preg_match(
                '/Euros?\s*:?\s*Compra\s*L?\s*([\d.]+)\s*Venta\s*L?\s*([\d.]+)/iu',
                $html,
                $eur,
            );
            if (empty($usd[1])) {
                return null;
            }

            return $this->armarTasasBac(
                (float) $usd[1],
                (float) ($usd[2] ?? $usd[1]),
                !empty($eur[1]) ? (float) $eur[1] : 0.0,
                !empty($eur[2]) ? (float) $eur[2] : 0.0,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string} */
    private function armarTasasBac(float $compraUsd, float $ventaUsd, float $compraEur, float $ventaEur): array
    {
        return [
            'usd'       => $compraUsd,
            'usd_venta' => $ventaUsd > 0 ? $ventaUsd : $compraUsd,
            'eur'       => $compraEur > 0 ? $compraEur : 0.0,
            'eur_venta' => $ventaEur > 0 ? $ventaEur : 0.0,
            'url'       => 'sucursalelectronica.com',
        ];
    }

    /**
     * @param list<array<string, mixed>> $filas
     * @return array{buy: float|int, sell: float|int}|null
     */
    private function buscarTasaBacHonduras(array $filas): ?array
    {
        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }
            if (strtoupper(trim((string) ($fila['country_code'] ?? ''))) !== 'HN') {
                continue;
            }
            if (strtoupper(trim((string) ($fila['currency_code'] ?? ''))) !== 'LPS') {
                continue;
            }

            return $fila;
        }

        return null;
    }

    private function fetchRespuestaBac(string $url, string $accept, int $segundos): string
    {
        $cabeceras = [
            'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept'          => $accept,
            'Accept-Language' => 'es-HN,es;q=0.9',
            'Referer'         => 'https://www.sucursalelectronica.com/',
        ];

        try {
            return $this->httpClient->request('GET', $url, [
                'timeout' => $segundos,
                'headers' => $cabeceras,
            ])->getContent();
        } catch (\Throwable) {
            $headerLine = "Accept: {$accept}\r\nAccept-Language: es-HN,es;q=0.9\r\nReferer: https://www.sucursalelectronica.com/\r\n";
            $contexto = stream_context_create([
                'http' => [
                    'timeout'       => $segundos,
                    'user_agent'    => $cabeceras['User-Agent'],
                    'header'        => $headerLine,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $cuerpo = @file_get_contents($url, false, $contexto);
            if ($cuerpo === false || $cuerpo === '') {
                throw new \RuntimeException('BAC no respondió: ' . $url);
            }

            return $cuerpo;
        }
    }

    private function fetch(string $url): string
    {
        return $this->fetchJson($url);
    }

    private function fetchJson(string $url): string
    {
        return $this->fetchJsonConTimeout($url, 15);
    }

    private function fetchJsonConTimeout(string $url, int $segundos): string
    {
        try {
            return $this->httpClient->request('GET', $url, [
                'timeout' => $segundos,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; Grova/1.0)',
                    'Accept'     => 'application/json, text/html, */*',
                ],
            ])->getContent();
        } catch (\Throwable) {
            $contexto = stream_context_create([
                'http' => [
                    'timeout'        => $segundos,
                    'user_agent'     => 'Mozilla/5.0 (compatible; Grova/1.0)',
                    'ignore_errors'  => true,
                ],
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $cuerpo = @file_get_contents($url, false, $contexto);
            if ($cuerpo === false || $cuerpo === '') {
                throw new \RuntimeException('No se pudo descargar: ' . $url);
            }

            return $cuerpo;
        }
    }

    /** @return array{banks:array,best_eur:string,best_usd:string,usd:float,usd_venta:float,eur:float,eur_venta:float,fecha:string,source:string} */
    private function fallback(): array
    {
        $banks = [
            'Atlántida' => ['usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 29.4971, 'eur_venta' => 33.5528, 'url' => 'bancatlan.hn'],
            'Ficohsa'   => ['usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 29.1089, 'eur_venta' => 33.5690, 'url' => 'ficohsa.hn'],
            'Occidente' => ['usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 0.0,     'eur_venta' => 0.0,     'url' => 'bancodeoccidente.hn'],
            'BAC'       => ['usd' => 26.6282, 'usd_venta' => 26.7613, 'eur' => 30.3561, 'eur_venta' => 32.6488, 'url' => 'sucursalelectronica.com'],
        ];
        return [
            'banks' => $banks, 'best_eur' => 'Atlántida', 'best_usd' => 'Atlántida',
            'usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 29.4971, 'eur_venta' => 33.5528,
            'fecha' => 'sin conexión', 'source' => 'fallback',
        ];
    }
}
