<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Repository;

use App\Module\Personal\Fishing\Entity\FishingFinca;
use App\Module\Personal\Fishing\Entity\FishingLure;
use App\Module\Personal\Fishing\Entity\FishingLureResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FishingLureResult> */
class FishingLureResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FishingLureResult::class);
    }

    /** @return FishingLureResult[] */
    public function findByFinca(FishingFinca $finca): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.lure', 'l')
            ->addSelect('l')
            ->where('r.finca = :finca')
            ->setParameter('finca', $finca)
            ->orderBy('r.funciono', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return FishingLureResult[] */
    public function findByLure(FishingLure $lure): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.finca', 'f')
            ->addSelect('f')
            ->where('r.lure = :lure')
            ->setParameter('lure', $lure)
            ->orderBy('r.funciono', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
