<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Persona;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Persona>
 */
class PersonaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Persona::class);
    }

    /** @return Persona[] */
    public function findResidentesByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.casa', 'c')
            ->addSelect('c')
            ->where('p.tenantId = :tid')
            ->andWhere('p.tipo IN (:tipos)')
            ->setParameter('tid', $tenantId)
            ->setParameter('tipos', ['propietario', 'inquilino'])
            ->orderBy('p.nombreCompleto', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Persona[] */
    public function findGuardiasActivos(int $tenantId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.tenantId = :tid')
            ->andWhere('p.tipo = :tipo')
            ->andWhere('p.estado = :estado')
            ->setParameter('tid', $tenantId)
            ->setParameter('tipo', 'personal_recurrente')
            ->setParameter('estado', 'activo')
            ->orderBy('p.nombreCompleto', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findResidenteByUser(int $userId, int $tenantId): ?Persona
    {
        return $this->createQueryBuilder('p')
            ->where('p.userId = :uid')
            ->andWhere('p.tenantId = :tid')
            ->andWhere('p.estado = :estado')
            ->setParameter('uid', $userId)
            ->setParameter('tid', $tenantId)
            ->setParameter('estado', 'activo')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserId(int $userId): ?\App\Module\Residencial\Entity\Persona
    {
        return $this->findOneBy(['userId' => $userId]);
    }
}
