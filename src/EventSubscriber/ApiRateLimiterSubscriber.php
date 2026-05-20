<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Rate limiting global para la API (por IP).
 * Evita abusos en endpoints públicos o autenticados.
 */
final class ApiRateLimiterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $apiGlobalLimiter,
        private readonly RateLimiterFactoryInterface $apiLoginLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        $ip = $request->getClientIp() ?? 'unknown';

        // Endpoint de login API tiene límite más restrictivo
        if ($path === '/api/auth/login' || $path === '/api/auth/register') {
            $limiter = $this->apiLoginLimiter->create($ip);
        } else {
            $limiter = $this->apiGlobalLimiter->create($ip);
        }

        $consume = $limiter->consume(1);

        if (!$consume->isAccepted()) {
            $retryAfter = $consume->getRetryAfter();
            $retryAfterSecs = $retryAfter !== null ? (int) ($retryAfter->getTimestamp() - time()) : 60;

            $response = new JsonResponse([
                'error' => 'Demasiadas solicitudes. Intenta de nuevo en unos segundos.',
                'retry_after' => $retryAfterSecs,
            ], Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string) $retryAfterSecs);

            $event->setResponse($response);
        }
    }
}
