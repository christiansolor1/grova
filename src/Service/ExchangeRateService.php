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
    private const CACHE_KEY = 'exchange_rates_multi_v8';

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
            $banks = $this->fetchTodos();

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
    private function parsearAtlantida(string $json): ?array
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || $data === []) {
            return null;
        }

        $hoy   = (new \DateTimeImmutable('now', new \DateTimeZone(self::ZONA_HONDURAS)))->format('d-m-Y');
        $fecha = isset($data[$hoy]) ? $hoy : (string) array_key_last($data);
        $dia   = $data[$fecha] ?? null;
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
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function parsearFicohsa(string $html): ?array
    {
        preg_match('/values-uno[^>]*>.*?buys-value[^>]*>\s*L\s*([\d.]+).*?sale-value[^>]*>\s*L\s*([\d.]+)/s', $html, $usd);
        preg_match('/values-dos[^>]*>.*?buys-value[^>]*>\s*L\s*([\d.]+).*?sale-value[^>]*>\s*L\s*([\d.]+)/s', $html, $eur);
        if (empty($usd[1]) || empty($eur[1])) {
            return null;
        }

        return ['usd' => (float) $usd[1], 'usd_venta' => (float) $usd[2], 'eur' => (float) $eur[1], 'eur_venta' => (float) $eur[2], 'url' => 'ficohsa.hn'];
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function parsearOccidente(string $html): ?array
    {
        preg_match('/Compra[^>]*>\s*LPS\.?\s*([\d.]+)/i', $html, $c);
        preg_match('/Venta[^>]*>\s*LPS\.?\s*([\d.]+)/i', $html, $v);
        if (empty($c[1])) {
            return null;
        }

        return ['usd' => (float) $c[1], 'usd_venta' => (float) ($v[1] ?? $c[1]), 'eur' => 0.0, 'eur_venta' => 0.0, 'url' => 'bancodeoccidente.hn'];
    }

    /** @return array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}|null */
    private function parsearBAC(string $xml): ?array
    {
        $doc = new \SimpleXMLElement($xml);
        foreach ($doc->country as $country) {
            if (strtolower(trim((string) $country->name)) === 'honduras') {
                $buyUsd = (float) $country->buyRateUSD;
                if ($buyUsd <= 0) {
                    return null;
                }

                return [
                    'usd'       => $buyUsd,
                    'usd_venta' => (float) $country->saleRateUSD,
                    'eur'       => (float) $country->buyRateEUR,
                    'eur_venta' => (float) $country->saleRateEUR,
                    'url'       => 'baccredomatic.com',
                ];
            }
        }

        return null;
    }

    /**
     * Lanza las 4 peticiones en paralelo y resuelve cada una individualmente.
     * Timeout máximo total ≈ 5 s (no 4 × 15 s en secuencia).
     *
     * @return array<string, array{usd:float,usd_venta:float,eur:float,eur_venta:float,url:string}>
     */
    private function fetchTodos(): array
    {
        $opciones = [
            'timeout' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Grova/1.0)',
                'Accept'     => 'application/json, text/html, */*',
            ],
        ];

        $respuestas = [
            'Atlántida' => $this->httpClient->request('GET', self::URL_JSON_ATLANTIDA, $opciones),
            'Ficohsa'   => $this->httpClient->request('GET', 'https://www.ficohsa.hn/', $opciones),
            'Occidente' => $this->httpClient->request('GET', 'https://www.bancodeoccidente.hn/enlinea/', $opciones),
            'BAC'       => $this->httpClient->request('GET', 'https://www.sucursalelectronica.com/exchangerate/showXmlExchangeRate.do', $opciones),
        ];

        $banks = [];

        foreach ($respuestas as $nombre => $respuesta) {
            try {
                $cuerpo = $respuesta->getContent();
                $banks[$nombre] = match ($nombre) {
                    'Atlántida' => $this->parsearAtlantida($cuerpo),
                    'Ficohsa'   => $this->parsearFicohsa($cuerpo),
                    'Occidente' => $this->parsearOccidente($cuerpo),
                    'BAC'       => $this->parsearBAC($cuerpo),
                    default     => null,
                };
            } catch (\Throwable) {
                $banks[$nombre] = null;
            }
        }

        return array_filter($banks, fn($b) => $b !== null);
    }

    private function fetch(string $url): string
    {
        return $this->httpClient->request('GET', $url, [
            'timeout' => 5,
            'headers' => ['User-Agent' => 'Mozilla/5.0 (compatible; Grova/1.0)', 'Accept' => '*/*'],
        ])->getContent();
    }

    /** @return array{banks:array,best_eur:string,best_usd:string,usd:float,usd_venta:float,eur:float,eur_venta:float,fecha:string,source:string} */
    private function fallback(): array
    {
        $banks = [
            'Atlántida' => ['usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 29.4971, 'eur_venta' => 33.5528, 'url' => 'bancatlan.hn'],
            'Ficohsa'   => ['usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 29.1089, 'eur_venta' => 33.5690, 'url' => 'ficohsa.hn'],
            'Occidente' => ['usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 0.0,     'eur_venta' => 0.0,     'url' => 'bancodeoccidente.hn'],
        ];
        return [
            'banks' => $banks, 'best_eur' => 'Atlántida', 'best_usd' => 'Atlántida',
            'usd' => 26.6151, 'usd_venta' => 26.7482, 'eur' => 29.4971, 'eur_venta' => 33.5528,
            'fecha' => 'sin conexión', 'source' => 'fallback',
        ];
    }
}
