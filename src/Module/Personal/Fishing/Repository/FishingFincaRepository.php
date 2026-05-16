<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Repository;

use App\Module\Personal\Fishing\Entity\FishingFinca;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FishingFinca> */
class FishingFincaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FishingFinca::class);
    }

    /** @return FishingFinca[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
