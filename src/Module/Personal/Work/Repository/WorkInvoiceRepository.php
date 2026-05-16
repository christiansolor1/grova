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
     * Facturas con fecha de envío o de pago dentro del mes calendario (mini calendario Work).
     *
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

    public function findNextNumero(): int
    {
        $max = $this->createQueryBuilder('i')
            ->select('MAX(i.numero)')
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? (int) $max + 1 : 1;
    }

    /** @return WorkInvoice[] */
    public function findLatest(int $limit = 12): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->addSelect('c')
            ->orderBy('i.anio', 'DESC')
            ->addOrderBy('i.mes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return WorkInvoice[] */
    public function findByAnio(int $anio): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->addSelect('c')
            ->where('i.anio = :anio')
            ->setParameter('anio', $anio)
            ->orderBy('i.mes', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Última factura con tasas de emisión guardadas (para el card de salario / FX). */
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
    public function findDistinctAnios(): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.anio')
            ->groupBy('i.anio')
            ->orderBy('i.anio', 'DESC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'anio');
    }

    /**
     * @return array<string, true> Keys "YYYY-MM" for paid invoices (solo el cliente indicado).
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
     * @return array<string, true> Keys "YYYY-MM" for months with an invoice (solo el cliente indicado).
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
