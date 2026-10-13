<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinCompromiso;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinCompromisoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinCompromiso::class);
    }

    /** @return FinCompromiso[] */
    public function findActivosByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.tenantId = :tid')->andWhere('c.activo = true')
            ->setParameter('tid', $tenantId)
            ->orderBy('c.bolsa', 'ASC')->addOrderBy('c.nombre', 'ASC')
            ->getQuery()->getResult();
    }
}
