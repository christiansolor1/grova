<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Repository;

use App\Module\Personal\Work\Entity\WorkClient;
use App\Module\Personal\Work\Entity\WorkInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WorkInvoice> */
class WorkInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkInvoice::class);
    }

    public function findForMonth(WorkClient $client, int $year, int $month): ?WorkInvoice
    {
        return $this->findOneBy(['client' => $client, 'anio' => $year, 'mes' => $month]);
    }

    /**
     * @return WorkInvoice[]
     */
    public function findWithEnvioOrPagoInCalendarMonth(WorkClient $client, int $year, int $month): array
    {
        $from = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $to   = $from->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('i')
            ->where('i.client = :c')
            ->andWhere('(i.enviadaAt IS NOT NULL AND i.enviadaAt >= :from AND i.enviadaAt <= :to) OR (i.pagadaAt IS NOT NULL AND i.pagadaAt >= :from AND i.pagadaAt <= :to)')
            ->setParameter('c', $client)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    public function findNextNumero(int $tenantId): int
    {
        $max = $this->createQueryBuilder('i')
            ->select('MAX(i.numero)')
            ->where('i.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? (int) $max + 1 : 1;
    }

    /** @return WorkInvoice[] */
    public function findLatest(int $tenantId, int $limit = 12): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->addSelect('c')
            ->where('i.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('i.anio', 'DESC')
            ->addOrderBy('i.mes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return WorkInvoice[] */
    public function findByAnio(int $tenantId, int $anio): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->addSelect('c')
            ->where('i.tenantId = :tenantId')
            ->andWhere('i.anio = :anio')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('anio', $anio)
            ->orderBy('i.mes', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestWithEmissionRates(WorkClient $client): ?WorkInvoice
    {
        return $this->createQueryBuilder('i')
            ->where('i.client = :c')
            ->andWhere('i.tasaEmisionEurL IS NOT NULL')
            ->setParameter('c', $client)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return int[] */
    public function findDistinctAnios(int $tenantId): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.anio')
            ->where('i.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->groupBy('i.anio')
            ->orderBy('i.anio', 'DESC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'anio');
    }

    /**
     * @return array<string, true>
     */
    public function findLockedMonths(?WorkClient $client = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.pagadaAt IS NOT NULL');
        if ($client !== null) {
            $qb->andWhere('i.client = :c')->setParameter('c', $client);
        }

        $map = [];
        foreach ($qb->getQuery()->getResult() as $invoice) {
            if (!$invoice instanceof WorkInvoice) {
                continue;
            }
            $map[sprintf('%04d-%02d', $invoice->getAnio(), $invoice->getMes())] = true;
        }

        return $map;
    }

    /**
     * @return array<string, true>
     */
    public function findInvoicedMonths(?WorkClient $client = null): array
    {
        $qb = $this->createQueryBuilder('i');
        if ($client !== null) {
            $qb->where('i.client = :c')->setParameter('c', $client);
        }

        $map = [];
        foreach ($qb->getQuery()->getResult() as $invoice) {
            if (!$invoice instanceof WorkInvoice) {
                continue;
            }
            $map[sprintf('%04d-%02d', $invoice->getAnio(), $invoice->getMes())] = true;
        }

        return $map;
    }
}
