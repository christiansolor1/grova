<?php

declare(strict_types=1);

namespace App\Service\Auth;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class ServicioGeolocalizacion
{
    private const CACHE_PREFIX = 'geoip_';
    private const CACHE_TTL    = 86400; // 24 horas
    private const API_URL      = 'http://ip-api.com/json/%s?fields=status,country,regionName,city,isp,query';

    private FilesystemAdapter $cache;

    public function __construct(string $cacheDir)
    {
        $this->cache = new FilesystemAdapter('geoip', 0, $cacheDir . '/geoip');
    }

    /**
     * @return array{ciudad: string, region: string, pais: string, isp: string}|null
     */
    public function localizar(string $ip): ?array
    {
        // IPs privadas / locales
        if ($ip === '0.0.0.0' || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [
                'ciudad' => 'Red local',
                'region' => '',
                'pais'   => 'Red local',
                'isp'    => 'Red local',
            ];
        }

        $cacheKey = self::CACHE_PREFIX . str_replace([':', '.'], '_', $ip);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($ip): ?array {
            $item->expiresAfter(self::CACHE_TTL);

            try {
                $response = @file_get_contents(sprintf(self::API_URL, $ip));
                if ($response === false) {
                    return null;
                }

                $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

                if (($data['status'] ?? '') !== 'success') {
                    return null;
                }

                return [
                    'ciudad' => $data['city'] ?? $data['regionName'] ?? 'Desconocida',
                    'region' => $data['regionName'] ?? '',
                    'pais'   => $data['country'] ?? 'Desconocido',
                    'isp'    => $data['isp'] ?? 'Desconocido',
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
