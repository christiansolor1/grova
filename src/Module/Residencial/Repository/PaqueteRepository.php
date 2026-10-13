<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Paquete;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paquete>
 */
class PaqueteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paquete::class);
    }

    /** @return Paquete[] */
    public function findPendientesByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.casa', 'c')
            ->addSelect('c')
            ->leftJoin('p.residente', 'r')
            ->addSelect('r')
            ->where('p.tenantId = :tid')
            ->andWhere('p.estado != :entregado')
            ->setParameter('tid', $tenantId)
            ->setParameter('entregado', 'entregado')
            ->orderBy('p.recibidoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPendientes(int $tenantId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.tenantId = :tid')
            ->andWhere('p.estado != :entregado')
            ->setParameter('tid', $tenantId)
            ->setParameter('entregado', 'entregado')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
