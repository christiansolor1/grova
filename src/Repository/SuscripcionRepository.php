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

    public function findUltimaForTenant(Tenant $tenant): ?Suscripcion
    {
        return $this->createQueryBuilder('s')
            ->where('s.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('s.fechaInicio', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActivaForTenant(Tenant $tenant): ?Suscripcion
    {
        return $this->findOneBy(['tenant' => $tenant, 'estado' => 'activa']);
    }

    /**
     * Suscripciones que siguen marcadas como 'activa' pero ya superaron su fecha de vencimiento.
     * Las usa el comando grova:suscripciones:vencer (manual o cron).
     *
     * @return list<Suscripcion>
     */
    public function findVencidasPendientes(): array
    {
        /** @var list<Suscripcion> */
        return $this->createQueryBuilder('s')
            ->where('s.estado = :activa')
            ->andWhere('s.fechaVencimiento < :hoy')
            ->setParameter('activa', 'activa')
            ->setParameter('hoy', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getResult();
    }
}
