<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Repository;

use App\Module\Personal\Wallet\Entity\WalletEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletEntry>
 */
class WalletEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletEntry::class);
    }

    /** @return WalletEntry[] */
    public function findLatest(int $limit = 40): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->addSelect('c')
            ->orderBy('e.fecha', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getSumByTipoAndMonth(string $tipo, int $year, int $month): float
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = (new \DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');

        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.monto) as total')
            ->where('e.tipo = :tipo')
            ->andWhere('e.fecha BETWEEN :from AND :to')
            ->setParameter('tipo', $tipo)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getSaldoTotal(): float
    {
        $ingresos = (float) ($this->createQueryBuilder('e')
            ->select('SUM(e.monto)')
            ->where('e.tipo = :t')->setParameter('t', 'ingreso')
            ->getQuery()->getSingleScalarResult() ?? 0);

        $gastos = (float) ($this->createQueryBuilder('e')
            ->select('SUM(e.monto)')
            ->where('e.tipo = :t')->setParameter('t', 'gasto')
            ->getQuery()->getSingleScalarResult() ?? 0);

        return $ingresos - $gastos;
    }
}
