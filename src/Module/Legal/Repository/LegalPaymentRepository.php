<?php

declare(strict_types=1);

namespace App\Module\Legal\Repository;

use App\Module\Legal\Entity\LegalPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<LegalPayment> */
class LegalPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalPayment::class);
    }
}
