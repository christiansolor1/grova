<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Repository;

use App\Module\Personal\Fishing\Entity\FishingTrip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FishingTrip> */
class FishingTripRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FishingTrip::class);
    }

    /** @return FishingTrip[] */
    public function findLatest(int $tenantId, int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.finca', 'f')
            ->addSelect('f')
            ->leftJoin('t.members', 'm')
            ->addSelect('m')
            ->leftJoin('t.expenses', 'e')
            ->addSelect('e')
            ->where('t.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('t.fecha', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndTenant(int $id, int $tenantId): ?FishingTrip
    {
        return $this->findOneBy(['id' => $id, 'tenantId' => $tenantId]);
    }
}
