<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TideService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire(env: 'STORMGLASS_API_KEY')]
        private readonly string $apiKey,
    ) {
    }

    /**
     * Obtiene las mareas para una ubicación y fecha.
     * El resultado se cachea 24 horas para no gastar requests del plan gratuito.
     *
     * @return array<int, array{time: string, height: float, type: string}>
     */
    public function getTides(float $lat, float $lng, \DateTimeInterface $date): array
    {
        $dateStr  = $date->format('Y-m-d');
        $cacheKey = sprintf('tides_%s_%s_%s', str_replace('.', '_', (string) $lat), str_replace('.', '_', (string) $lng), $dateStr);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lat, $lng, $dateStr): array {
            // Cache de 24 horas — 1 sola llamada a la API por finca por día
            $item->expiresAfter(86400);

            $start = (new \DateTimeImmutable($dateStr . ' 00:00:00'))->getTimestamp();
            $end   = (new \DateTimeImmutable($dateStr . ' 23:59:59'))->getTimestamp();

            try {
                $response = $this->httpClient->request('GET', 'https://api.stormglass.io/v2/tide/extremes/point', [
                    'headers' => ['Authorization' => $this->apiKey],
                    'query'   => [
                        'lat'   => $lat,
                        'lng'   => $lng,
                        'start' => $start,
                        'end'   => $end,
                    ],
                    'timeout' => 8,
                ]);

                $data  = $response->toArray();
                $tides = [];

                foreach ($data['data'] ?? [] as $item2) {
                    $tides[] = [
                        'time'   => (new \DateTimeImmutable($item2['time']))->format('H:i'),
                        'height' => round((float) $item2['height'], 2),
                        'type'   => $item2['type'] === 'high' ? 'Alta' : 'Baja',
                    ];
                }

                return $tides;
            } catch (\Throwable) {
                // Si falla no cachear — el usuario puede reintentar
                $item->expiresAfter(0);
                return [];
            }
        });
    }
}
