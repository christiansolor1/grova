<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinCuota;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinCuotaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinCuota::class);
    }

    /** @return FinCuota[] */
    public function findByCompromisoMes(int $compromisoId, int $anio, int $mes): array
    {
        $desde = new \DateTimeImmutable("{$anio}-{$mes}-01");
        $hasta = $desde->modify('last day of this month');
        return $this->createQueryBuilder('q')
            ->where('q.compromiso = :cid')
            ->andWhere('q.fechaVencimiento BETWEEN :desde AND :hasta')
            ->setParameter('cid', $compromisoId)
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->getQuery()->getResult();
    }

    /**
     * Devuelve ['total' => float, 'pagado' => float] por compromiso_id para un tenant.
     * @return array<int, array{total: float, pagado: float}>
     */
    public function resumenPorCompromiso(int $tenantId): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT c.compromiso_id,
                    COALESCE(SUM(c.monto_total), 0)  AS total,
                    COALESCE(SUM(c.monto_pagado), 0) AS pagado
             FROM fin_cuota c
             JOIN fin_compromiso cp ON cp.id = c.compromiso_id
             WHERE cp.tenant_id = :tid
             GROUP BY c.compromiso_id',
            ['tid' => $tenantId]
        );

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int)$row['compromiso_id']] = [
                'total'  => (float)$row['total'],
                'pagado' => (float)$row['pagado'],
            ];
        }
        return $resultado;
    }

    /** @return FinCuota[] todas las cuotas de un compromiso, ordenadas por fecha */
    public function findByCompromiso(int $compromisoId): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.compromiso = :cid')
            ->setParameter('cid', $compromisoId)
            ->orderBy('q.fechaVencimiento', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return FinCuota[] cuotas pendientes/vencidas del tenant en los próximos 60 días */
    public function findProximasByTenant(int $tenantId, int $dias = 60): array
    {
        $hoy   = new \DateTimeImmutable('today');
        $hasta = $hoy->modify("+{$dias} days");
        return $this->createQueryBuilder('q')
            ->join('q.compromiso', 'c')
            ->where('c.tenantId = :tid')
            ->andWhere('q.fechaVencimiento <= :hasta')
            ->andWhere('q.montoPagado < q.montoTotal')
            ->setParameter('tid', $tenantId)
            ->setParameter('hasta', $hasta)
            ->orderBy('q.fechaVencimiento', 'ASC')
            ->getQuery()->getResult();
    }
}
