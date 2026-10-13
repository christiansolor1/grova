<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinInversion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinInversionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinInversion::class);
    }

    /** @return FinInversion[] */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('i.activa', 'DESC')
            ->addOrderBy('i.valorActualHnl', 'DESC')
            ->getQuery()->getResult();
    }

    public function sumValorActualByTenant(int $tenantId): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.valorActualHnl)')
            ->where('i.tenantId = :tid')
            ->andWhere('i.activa = true')
            ->setParameter('tid', $tenantId)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    public function sumInvertidoByTenant(int $tenantId): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.montoInvertidoHnl)')
            ->where('i.tenantId = :tid')
            ->andWhere('i.activa = true')
            ->setParameter('tid', $tenantId)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }
}
