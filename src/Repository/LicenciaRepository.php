<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Licencia;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Licencia>
 */
class LicenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Licencia::class);
    }

    /**
     * @return list<Licencia>
     */
    public function findByTenant(Tenant $tenant): array
    {
        /** @var list<Licencia> */
        return $this->createQueryBuilder('l')
            ->where('l.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('l.fechaEmision', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActivaVigenteByTenant(Tenant $tenant): ?Licencia
    {
        return $this->createQueryBuilder('l')
            ->where('l.tenant = :tenant')
            ->andWhere('l.estado = :activa')
            ->andWhere('l.fechaVencimiento >= :hoy')
            ->setParameter('tenant', $tenant)
            ->setParameter('activa', 'activa')
            ->setParameter('hoy', new \DateTimeImmutable('today'))
            ->orderBy('l.fechaVencimiento', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
