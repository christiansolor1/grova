<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Suscripcion;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Suscripcion>
 */
class SuscripcionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Suscripcion::class);
    }

    public function findActivaForTenant(Tenant $tenant): ?Suscripcion
    {
        return $this->findOneBy(['tenant' => $tenant, 'estado' => 'activa']);
    }
}
