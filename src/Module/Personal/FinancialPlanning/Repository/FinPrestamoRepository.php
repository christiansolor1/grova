<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinPrestamo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinPrestamoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinPrestamo::class);
    }
}
