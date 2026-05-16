<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Repository;

use App\Module\Personal\Fishing\Entity\FishingTripMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FishingTripMember> */
class FishingTripMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FishingTripMember::class);
    }
}
