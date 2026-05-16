<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Repository;

use App\Module\Personal\Fishing\Entity\FishingFinca;
use App\Module\Personal\Fishing\Entity\FishingSpot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FishingSpot> */
class FishingSpotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FishingSpot::class);
    }

    /** @return FishingSpot[] */
    public function findByFinca(FishingFinca $finca): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.finca = :finca')
            ->setParameter('finca', $finca)
            ->orderBy('s.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
