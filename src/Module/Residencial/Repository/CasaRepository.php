<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Casa;
use App\Module\Residencial\Entity\Residencial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Casa>
 */
class CasaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Casa::class);
    }

    /** @return Casa[] */
    public function findByResidencial(Residencial $residencial): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.manzana', 'm')
            ->addSelect('m')
            ->where('c.residencial = :res')
            ->setParameter('res', $residencial)
            ->orderBy('m.nombre', 'ASC')
            ->addOrderBy('c.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Casa[] */
    public function findHabitadasByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.tenantId = :tid')
            ->andWhere('c.estado = :estado')
            ->setParameter('tid', $tenantId)
            ->setParameter('estado', 'habitada')
            ->orderBy('c.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
