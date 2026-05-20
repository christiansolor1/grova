<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Guarda la versión de sesión al iniciar sesión y verifica en cada request
 * que la versión almacenada coincida con la del usuario.
 * Si no coincide (por cambio de contraseña o cierre de otras sesiones),
 * invalida la sesión y redirige al login.
 */
final class SessionVersionSubscriber implements EventSubscriberInterface
{
    private const SESSION_KEY = '_session_version';
    private const SESSION_TOKEN_KEY = '_session_token';

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', 0],
            KernelEvents::REQUEST => ['onKernelRequest', 15],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $session->set(self::SESSION_KEY, $user->getSessionVersion());
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            return;
        }

        // Rutas que nunca deben redirigir
        $route = (string) $request->attributes->get('_route', '');
        if (str_starts_with($route, 'login') || str_starts_with($route, 'logout')
            || str_starts_with($route, '2fa') || $route === 'grova_lock_screen') {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // ── Chequeo de sessionVersion (cierre global) ──
        $storedVersion = $session->get(self::SESSION_KEY);
        if ($storedVersion !== null && $storedVersion !== $user->getSessionVersion()) {
            $session->invalidate();
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('login')));

            return;
        }

        // ── Chequeo de revocación individual de dispositivo ──
        $sessionToken = $session->get(self::SESSION_TOKEN_KEY);
        if ($sessionToken !== null) {
            $revoked = $user->getRevokedSessionTokens();
            if (\in_array($sessionToken, $revoked, true)) {
                $session->invalidate();
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('login')));

                return;
            }
        }
    }
}
