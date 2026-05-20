<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class VerificadorUsuario implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // nada antes de cargar credenciales
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEmailVerificado()) {
            throw new CustomUserMessageAuthenticationException(
                'Debes verificar tu email antes de iniciar sesión. Revisa tu bandeja de entrada.'
            );
        }
    }
}
