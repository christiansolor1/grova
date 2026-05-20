<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Service\GuardiaSubscripcion;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Bloquea el acceso si la suscripción del tenant venció.
 * Corre en priority -30, después de TenantContextSubscriber (-20).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: -30)]
final class EscuchadorSubscripcion
{
    /** @var list<string> Prefijos de ruta que no requieren suscripción activa */
    private const RUTAS_LIBRES = [
        'app_subscripcion_vencida',
        'login',
        'app_logout',
        'app_setup',
        '_wdt',
        '_profiler',
    ];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly GuardiaSubscripcion $guardia,
        private readonly UrlGeneratorInterface $generadorUrls,
    ) {
    }

    public function __invoke(RequestEvent $evento): void
    {
        if (!$evento->isMainRequest()) {
            return;
        }

        $peticion = $evento->getRequest();

        if (preg_match('#^/_(profiler|wdt)#', $peticion->getPathInfo())) {
            return;
        }

        $ruta = (string) $peticion->attributes->get('_route', '');

        foreach (self::RUTAS_LIBRES as $libre) {
            if (str_starts_with($ruta, $libre)) {
                return;
            }
        }

        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return;
        }

        $usuario = $token->getUser();

        if (!$usuario instanceof User) {
            return;
        }

        $estado = $this->guardia->verificar($usuario);

        if ($estado->esBloqueante()) {
            $evento->setResponse(new RedirectResponse(
                $this->generadorUrls->generate('app_subscripcion_vencida'),
            ));
        }
    }
}
