<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: RequestEvent::class, priority: 15)]
final class LocaleSubscriber
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // No sobreescribir si la ruta no tiene _locale (ej. webprofiler)
        if (!$request->attributes->has('_locale')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        $preferred = $user->getPreferredLocale();
        if ($preferred !== null && in_array($preferred, ['es', 'en'], true)) {
            $request->setLocale($preferred);
        }
    }
}
