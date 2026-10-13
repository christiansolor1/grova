<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Repository;

use App\Module\Personal\Wallet\Entity\WalletInstitucion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletInstitucion>
 */
class WalletInstitucionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletInstitucion::class);
    }

    /** @return WalletInstitucion[] */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('i.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByNombre(int $tenantId, string $nombre): ?WalletInstitucion
    {
        return $this->createQueryBuilder('i')
            ->where('i.tenantId = :tid AND LOWER(i.nombre) = LOWER(:nombre)')
            ->setParameter('tid', $tenantId)
            ->setParameter('nombre', $nombre)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
