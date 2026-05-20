<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Plan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plan>
 */
class PlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plan::class);
    }

    /**
     * @return list<Plan>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['precioMensual' => 'ASC']);
    }

    /**
     * @return list<Plan>
     */
    public function findActivos(): array
    {
        return $this->findBy(['estado' => 'activo'], ['precioMensual' => 'ASC']);
    }

    public function findByNombre(string $nombre): ?Plan
    {
        return $this->findOneBy(['nombre' => $nombre]);
    }
}
