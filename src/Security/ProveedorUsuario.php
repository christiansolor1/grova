<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Permite login con email o con el slug del workspace (ej. "familia-solorzano").
 *
 * @implements UserProviderInterface<User>
 */
final class ProveedorUsuario implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $emCore,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $repo = $this->emCore->getRepository(User::class);

        // 1. Intenta por email
        $usuario = $repo->findOneBy(['email' => $identifier]);

        // 2. Intenta por username (slug visible elegido en el registro)
        if (!$usuario instanceof User) {
            $usuario = $repo->findOneBy(['username' => $identifier]);
        }

        if (!$usuario instanceof User) {
            throw new UserNotFoundException(sprintf('Usuario "%s" no encontrado.', $identifier));
        }

        return $usuario;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Tipo "%s" no soportado.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Tipo "%s" no soportado.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->emCore->flush();
    }
}
