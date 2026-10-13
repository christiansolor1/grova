<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Repository;

use App\Module\Personal\Wallet\Entity\WalletGastoFijo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WalletGastoFijoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletGastoFijo::class);
    }

    /** @return WalletGastoFijo[] */
    public function findActivos(int $tenantId): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.tenantId = :tid AND g.activo = true')
            ->setParameter('tid', $tenantId)
            ->orderBy('g.diaPago', 'ASC')
            ->addOrderBy('g.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return WalletGastoFijo[] */
    public function findTodos(int $tenantId): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('g.activo', 'DESC')
            ->addOrderBy('g.diaPago', 'ASC')
            ->addOrderBy('g.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
