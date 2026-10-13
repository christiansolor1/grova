<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Residencial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Residencial>
 */
class ResidencialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Residencial::class);
    }

    /** @return Residencial[] */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('r.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPrimeroActivo(int $tenantId): ?Residencial
    {
        return $this->createQueryBuilder('r')
            ->where('r.tenantId = :tid')
            ->andWhere('r.estado = :estado')
            ->setParameter('tid', $tenantId)
            ->setParameter('estado', 'activa')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
