<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Repository;

use App\Module\Personal\Fishing\Entity\FishingLure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FishingLure> */
class FishingLureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FishingLure::class);
    }

    /** @return FishingLure[] */
    public function findAllOrdered(int $tenantId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('l.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndTenant(int $id, int $tenantId): ?FishingLure
    {
        return $this->findOneBy(['id' => $id, 'tenantId' => $tenantId]);
    }
}
