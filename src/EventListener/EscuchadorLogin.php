<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\LogActividad;
use App\Repository\UserRepository;
use App\Service\Auth\ServicioLogActividad;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class EscuchadorLogin
{
    public function __construct(
        private readonly ServicioLogActividad $log,
        private readonly UserRepository $userRepo,
    ) {
    }

    #[AsEventListener(event: LoginFailureEvent::class)]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $email = $event->getPassport()?->getUser()?->getUserIdentifier()
            ?? $event->getRequest()->request->get('username', 'unknown');

        $this->log->registrar(
            accion: LogActividad::ACCION_LOGIN_FALLIDO,
            email: $email,
        );
    }

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof \App\Entity\User) {
            return;
        }

        $this->log->registrar(
            accion: LogActividad::ACCION_LOGIN_EXITOSO,
            usuario: $user,
            email: $user->getEmail(),
        );
    }
}
