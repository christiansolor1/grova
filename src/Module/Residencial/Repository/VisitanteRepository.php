<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Visitante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visitante>
 */
class VisitanteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visitante::class);
    }

    public function findByDni(int $tenantId, string $dni): ?Visitante
    {
        return $this->createQueryBuilder('v')
            ->where('v.tenantId = :tid')
            ->andWhere('v.dni = :dni')
            ->setParameter('tid', $tenantId)
            ->setParameter('dni', $dni)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Visitante[] */
    public function buscar(int $tenantId, string $termino): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.tenantId = :tid')
            ->andWhere('v.nombreCompleto LIKE :q OR v.dni LIKE :q')
            ->setParameter('tid', $tenantId)
            ->setParameter('q', '%' . $termino . '%')
            ->orderBy('v.nombreCompleto', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}
