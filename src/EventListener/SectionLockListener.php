<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Service\SectionLockService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 5)]
final class SectionLockListener
{
    public function __construct(
        private readonly SectionLockService $lockService,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $request = $event->getRequest();
        $route   = (string) $request->attributes->get('_route', '');

        // No interceptar rutas del propio sistema de lock ni login
        if (str_starts_with($route, 'grova_lock_') || str_starts_with($route, 'grova_login_')) return;

        $section = $this->lockService->getSectionFromRoute($route);
        if ($section === null) return;

        $token = $this->tokenStorage->getToken();
        $user  = $token?->getUser();
        if (!$user instanceof User) return;

        if (!$this->lockService->isSectionLocked($user, $section)) return;

        $session = $request->getSession();
        if ($this->lockService->isSessionUnlocked($session, $user, $section)) return;

        // Redirigir a la pantalla de bloqueo
        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('grova_lock_screen', [
                '_locale'  => $request->getLocale(),
                'section'  => $section,
                'redirect' => $request->getUri(),
            ])
        ));
    }
}
