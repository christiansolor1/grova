<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Repository;

use App\Module\Personal\Work\Entity\WorkClient;
use App\Module\Personal\Work\Entity\WorkDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WorkDay> */
class WorkDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkDay::class);
    }

    /** @return WorkDay[] */
    public function findByMonth(int $tenantId, int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = (new \DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');

        return $this->createQueryBuilder('d')
            ->where('d.tenantId = :tenantId')
            ->andWhere('d.fecha BETWEEN :from AND :to')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('d.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countWorkedDaysInMonth(WorkClient $client, int $year, int $month): int
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = (new \DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');

        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.client = :client')
            ->andWhere('d.fecha BETWEEN :from AND :to')
            ->andWhere('d.horaEntrada IS NOT NULL')
            ->andWhere('d.esFeriado = false')
            ->andWhere('d.esVacacion = false')
            ->setParameter('client', $client)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return WorkDay[] */
    public function findWorkedWithBonusInMonth(WorkClient $client, int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = (new \DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');

        return $this->createQueryBuilder('d')
            ->where('d.client = :client')
            ->andWhere('d.fecha BETWEEN :from AND :to')
            ->andWhere('d.horaEntrada IS NOT NULL')
            ->andWhere('d.esFeriado = false')
            ->andWhere('d.esVacacion = false')
            ->setParameter('client', $client)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /** @return WorkDay[] */
    public function findLatest(int $tenantId, int $limit = 30): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->addSelect('c')
            ->where('d.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('d.fecha', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return WorkDay[] */
    public function findAllSorted(int $tenantId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('d.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{year: int, month: int}>
     */
    public function findDistinctMonths(int $tenantId): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT DISTINCT YEAR(fecha) AS y, MONTH(fecha) AS m FROM work_day WHERE tenant_id = ? ORDER BY y ASC, m ASC',
            [$tenantId],
        );

        return array_map(fn($r) => ['year' => (int) $r['y'], 'month' => (int) $r['m']], $rows);
    }
}
