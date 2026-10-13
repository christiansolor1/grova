<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinBolsa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinBolsaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinBolsa::class);
    }

    /** @return FinBolsa[] */
    public function findActivasByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.tenantId = :tid')->andWhere('b.activa = true')
            ->setParameter('tid', $tenantId)
            ->orderBy('b.orden', 'ASC')
            ->getQuery()->getResult();
    }
}
