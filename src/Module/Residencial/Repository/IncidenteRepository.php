<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Incidente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Incidente>
 */
class IncidenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Incidente::class);
    }

    /** @return Incidente[] */
    public function findByTenant(int $tenantId, int $limit = 50): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.guardia', 'g')
            ->addSelect('g')
            ->where('i.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('i.creadoEn', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countDelMes(int $tenantId): int
    {
        $inicio = new \DateTimeImmutable('first day of this month midnight');
        $fin    = new \DateTimeImmutable('first day of next month midnight');

        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.tenantId = :tid')
            ->andWhere('i.creadoEn >= :inicio')
            ->andWhere('i.creadoEn < :fin')
            ->setParameter('tid', $tenantId)
            ->setParameter('inicio', $inicio)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
