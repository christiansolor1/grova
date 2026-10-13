<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Manzana;
use App\Module\Residencial\Entity\Residencial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Manzana>
 */
class ManzanaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manzana::class);
    }

    /** @return Manzana[] */
    public function findByResidencial(Residencial $residencial): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.residencial = :res')
            ->setParameter('res', $residencial)
            ->orderBy('m.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
