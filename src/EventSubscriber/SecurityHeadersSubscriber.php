<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $kernelEnvironment,
    ) {
    }
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers  = $response->headers;

        // Evita que el sitio sea embebido en iframes (clickjacking)
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Evita que el browser adivine el content-type
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Controla qué información de referencia se envía
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Fuerza HTTPS en producción (HSTS)
        if ($event->getRequest()->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Permisos de APIs del browser
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // En dev no aplicar CSP estricta: choca con la Web Debug Toolbar (/_wdt, bundles/webprofiler, etc.)
        if ($this->kernelEnvironment === 'dev') {
            return;
        }

        $headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdn.datatables.net; " .
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdn.datatables.net; " .
            "font-src 'self' cdn.jsdelivr.net; " .
            "img-src 'self' data:; " .
            "connect-src 'self';"
        );
    }
}
