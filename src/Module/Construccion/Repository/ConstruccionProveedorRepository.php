<?php

declare(strict_types=1);

namespace App\Module\Construccion\Repository;

use App\Module\Construccion\Entity\ConstruccionProveedor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConstruccionProveedor>
 */
class ConstruccionProveedorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConstruccionProveedor::class);
    }

    /** @return ConstruccionProveedor[] */
    public function findActivos(): array
    {
        return $this->findBy(['activo' => true], ['nombre' => 'ASC']);
    }
}
