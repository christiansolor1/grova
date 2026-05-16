<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ModuloTenant;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuloTenant>
 */
class ModuloTenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuloTenant::class);
    }

    /**
     * Retorna los modulo_key que están activos para el tenant dado.
     *
     * @return list<string>
     */
    public function findActiveKeysForTenant(Tenant $tenant): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.moduloKey')
            ->where('m.tenant = :tenant')
            ->andWhere('m.activo = true')
            ->setParameter('tenant', $tenant)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'moduloKey');
    }
}
