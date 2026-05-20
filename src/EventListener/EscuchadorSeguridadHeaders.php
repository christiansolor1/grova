<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class EscuchadorSeguridadHeaders
{
    // Lista blanca de recursos permitidos en CSP
    private const CDN_JSDELIVR    = 'https://cdn.jsdelivr.net';
    private const CDN_CLOUDFLARE  = 'https://cdnjs.cloudflare.com';
    private const CDN_DATATABLES  = 'https://cdn.datatables.net';
    private const RECAPTCHA_SCRIPT = 'https://www.google.com/recaptcha/';
    private const RECAPTCHA_GS    = 'https://www.gstatic.com/recaptcha/';
    private const TURNSTILE       = 'https://challenges.cloudflare.com';

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $respuesta = $event->getResponse();

        // HSTS — obliga HTTPS por 1 año (incluye subdominios)
        $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Evita que la página se cargue en un iframe (clickjacking)
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Evita que el navegador sniffee el MIME type
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer Policy — no enviar referrer al navegar a otros sitios
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy — desactivar APIs que no usamos
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content-Security-Policy
        $csp = [
            "default-src 'self'",

            "script-src 'self' 'unsafe-inline' "
                .self::CDN_JSDELIVR.' '
                .self::CDN_CLOUDFLARE.' '
                .self::CDN_DATATABLES.' '
                .self::RECAPTCHA_SCRIPT.' '
                .self::RECAPTCHA_GS.' '
                .self::TURNSTILE,

            "style-src 'self' 'unsafe-inline' "
                .self::CDN_JSDELIVR.' '
                .self::CDN_CLOUDFLARE.' '
                .self::CDN_DATATABLES,

            "connect-src 'self' "
                .self::CDN_JSDELIVR.' '
                .self::CDN_CLOUDFLARE.' '
                .self::CDN_DATATABLES,

            "img-src 'self' data: blob: "
                .self::CDN_JSDELIVR.' '
                .self::CDN_CLOUDFLARE.' '
                .self::CDN_DATATABLES,

            "frame-src 'self' "
                .self::RECAPTCHA_SCRIPT.' '
                .self::TURNSTILE,

            "font-src 'self' data: "
                .self::CDN_JSDELIVR.' '
                .self::CDN_CLOUDFLARE,

            "worker-src 'self' blob:",
        ];

        $respuesta->headers->set('Content-Security-Policy', implode('; ', $csp));
    }
}
