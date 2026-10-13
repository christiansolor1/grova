<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Repository;

use App\Module\Personal\Wallet\Entity\WalletTransferencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletTransferencia>
 */
class WalletTransferenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletTransferencia::class);
    }

    /** @return WalletTransferencia[] */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.cuentaOrigen', 'co')->addSelect('co')
            ->leftJoin('t.cuentaDestino', 'cd')->addSelect('cd')
            ->where('t.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('t.fecha', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
