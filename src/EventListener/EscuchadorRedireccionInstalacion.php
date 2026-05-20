<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\Setup\EstadoInstalacion;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Redirige al instalador mientras no exista .env.local.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 256)]
final class EscuchadorRedireccionInstalacion
{
    /** @var list<string> */
    private const PREFIJOS_RUTA_PERMITIDOS = [
        'app_setup',
        '_wdt',
        '_profiler',
    ];

    public function __construct(
        private readonly EstadoInstalacion $estadoInstalacion,
        private readonly UrlGeneratorInterface $generadorUrls,
    ) {
    }

    public function __invoke(RequestEvent $evento): void
    {
        if (!$evento->isMainRequest() || $this->estadoInstalacion->estaInstalado()) {
            return;
        }

        $peticion = $evento->getRequest();
        $ruta = $peticion->getPathInfo();

        if (str_starts_with($ruta, '/setup')) {
            return;
        }

        if (preg_match('#^/_(profiler|wdt)#', $ruta)) {
            return;
        }

        $nombreRuta = (string) $peticion->attributes->get('_route', '');
        foreach (self::PREFIJOS_RUTA_PERMITIDOS as $prefijo) {
            if ($nombreRuta !== '' && str_starts_with($nombreRuta, $prefijo)) {
                return;
            }
        }

        $evento->setResponse(new RedirectResponse(
            $this->generadorUrls->generate('app_setup'),
        ));
    }
}
