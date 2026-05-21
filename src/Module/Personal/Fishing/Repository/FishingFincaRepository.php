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
    public function findAllOrdered(int $tenantId): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('f.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndTenant(int $id, int $tenantId): ?FishingFinca
    {
        return $this->findOneBy(['id' => $id, 'tenantId' => $tenantId]);
    }
}
