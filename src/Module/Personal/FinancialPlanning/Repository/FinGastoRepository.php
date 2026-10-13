<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinGasto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinGastoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinGasto::class);
    }

    /** @return FinGasto[] */
    public function findByTenantMes(int $tenantId, int $anio, int $mes): array
    {
        $desde = new \DateTimeImmutable("{$anio}-{$mes}-01");
        $hasta = $desde->modify('last day of this month');
        return $this->createQueryBuilder('g')
            ->where('g.tenantId = :tid')
            ->andWhere('g.fecha BETWEEN :desde AND :hasta')
            ->setParameter('tid', $tenantId)
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->orderBy('g.fecha', 'DESC')
            ->getQuery()->getResult();
    }

    public function sumMontoHnlByTenant(int $tenantId): float
    {
        $result = $this->createQueryBuilder('g')
            ->select('SUM(g.montoHnl)')
            ->where('g.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    /** @return FinGasto[] */
    public function findRecentByTenant(int $tenantId, int $limit = 5): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('g.fecha', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
