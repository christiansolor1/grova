<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserCredencialBiometrica;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCredencialBiometrica>
 */
class UserCredencialBiometricaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCredencialBiometrica::class);
    }

    /** @return UserCredencialBiometrica[] */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['creadoEn' => 'ASC']);
    }

    public function findByCredentialId(string $credentialId): ?UserCredencialBiometrica
    {
        return $this->findOneBy(['credentialId' => $credentialId]);
    }

    /** IDs de credencial para usar en WebAuthn getGetArgs */
    public function findCredentialIdsByUser(User $user): array
    {
        return array_map(
            fn(UserCredencialBiometrica $c) => base64_decode($c->getCredentialId()),
            $this->findByUser($user),
        );
    }
}
