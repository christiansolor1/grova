<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    private const CDN_JS     = 'cdn.jsdelivr.net';
    private const CDN_DT     = 'cdn.datatables.net';
    private const CDN_JSZIP  = 'cdnjs.cloudflare.com';
    private const RECAPTCHA  = 'www.google.com';
    private const RECAPTCHA2 = 'www.gstatic.com';

    public function __construct(
        #[Autowire('%kernel.environment%')]
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

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Excluir rutas del Web Profiler para que funcione correctamente
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler')) {
            return;
        }

        $response = $event->getResponse();
        $headers  = $response->headers;

        // Clickjacking protection
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        // MIME sniffing protection
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer policy
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS — siempre en prod, incluso detrás de proxy
        if ($this->kernelEnvironment !== 'dev') {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Permissions Policy
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // CSP
        $csp = $this->buildCsp();
        $headers->set('Content-Security-Policy', $csp);
    }

    private function buildCsp(): string
    {
        $base = "default-src 'self'; ";

        $scriptSrc = sprintf(
            "script-src 'self' 'unsafe-inline' %s %s %s %s %s; ",
            self::CDN_JS,
            self::CDN_DT,
            self::CDN_JSZIP,
            self::RECAPTCHA,
            self::RECAPTCHA2,
        );

        $styleSrc = sprintf(
            "style-src 'self' 'unsafe-inline' %s %s %s; ",
            self::CDN_JS,
            self::CDN_DT,
            self::RECAPTCHA,
        );

        $fontSrc = sprintf(
            "font-src 'self' %s; ",
            self::CDN_JS,
        );

        $imgSrc = sprintf(
            "img-src 'self' data: %s %s %s; ",
            self::CDN_DT,
            self::RECAPTCHA,
            self::RECAPTCHA2,
        );

        $connectSrc = sprintf(
            "connect-src 'self' %s %s; ",
            self::CDN_DT,
            self::RECAPTCHA,
        );

        // frame-src necesario para reCAPTCHA v2 (widget en iframe)
        $frameSrc = sprintf(
            "frame-src %s; ",
            self::RECAPTCHA,
        );

        return $base . $scriptSrc . $styleSrc . $fontSrc . $imgSrc . $connectSrc . $frameSrc;
    }
}
