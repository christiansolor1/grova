<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinIngreso;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinIngresoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinIngreso::class);
    }

    /** @return FinIngreso[] */
    public function findByTenantMes(int $tenantId, int $anio, int $mes): array
    {
        $desde = new \DateTimeImmutable("{$anio}-{$mes}-01");
        $hasta = $desde->modify('last day of this month');
        return $this->createQueryBuilder('i')
            ->where('i.tenantId = :tid')
            ->andWhere('i.fecha BETWEEN :desde AND :hasta')
            ->setParameter('tid', $tenantId)
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->orderBy('i.fecha', 'DESC')
            ->getQuery()->getResult();
    }

    public function sumNetoHnlByTenant(int $tenantId): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.netoHnl)')
            ->where('i.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    public function sumAhorroByTenant(int $tenantId): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.ahorroReservadoHnl)')
            ->where('i.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    /** @return FinIngreso[] */
    public function findRecentByTenant(int $tenantId, int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('i.fecha', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
