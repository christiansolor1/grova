<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinAcreedor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinAcreedorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinAcreedor::class);
    }

    /** @return FinAcreedor[] */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('a.activo', 'DESC')
            ->addOrderBy('a.saldoHnl', 'DESC')
            ->getQuery()->getResult();
    }

    public function sumSaldoActivosByTenant(int $tenantId): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('SUM(a.saldoHnl)')
            ->where('a.tenantId = :tid')
            ->andWhere('a.activo = true')
            ->setParameter('tid', $tenantId)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }
}
