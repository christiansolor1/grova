<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Repository;

use App\Module\Personal\Wallet\Entity\WalletCuenta;
use App\Module\Personal\Wallet\Entity\WalletInstitucion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletCuenta>
 */
class WalletCuentaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletCuenta::class);
    }

    /** @return WalletCuenta[] */
    public function findActivas(int $tenantId): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.institucion', 'i')->addSelect('i')
            ->where('c.tenantId = :tid AND c.activo = true')
            ->setParameter('tid', $tenantId)
            ->orderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return WalletCuenta[] */
    public function findAll_(int $tenantId): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.institucion', 'i')->addSelect('i')
            ->where('c.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('c.activo', 'DESC')
            ->addOrderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Saldo calculado de una cuenta:
     * - Crédito: gastos − ingresos (cuánto debés)
     * - Resto: saldoInicial + ingresos − gastos
     */
    public function getSaldo(WalletCuenta $cuenta): float
    {
        $rows = $this->getEntityManager()->createQuery(
            'SELECT e.tipo, SUM(e.monto) AS total
             FROM App\Module\Personal\Wallet\Entity\WalletEntry e
             WHERE e.cuenta = :cuenta
             GROUP BY e.tipo'
        )->setParameter('cuenta', $cuenta)->getResult();

        $ingresos = 0.0;
        $gastos   = 0.0;
        foreach ($rows as $row) {
            if ($row['tipo'] === 'ingreso') {
                $ingresos = (float) $row['total'];
            } elseif ($row['tipo'] === 'gasto') {
                $gastos = (float) $row['total'];
            }
        }

        if ($cuenta->isCredito()) {
            return $gastos - $ingresos; // positivo = cuánto debés
        }

        return $cuenta->getSaldoInicialFloat() + $ingresos - $gastos;
    }

    /** @return WalletCuenta[] */
    public function findByInstitucion(int $tenantId, WalletInstitucion $inst): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.tenantId = :tid AND c.institucion = :inst')
            ->setParameter('tid', $tenantId)
            ->setParameter('inst', $inst)
            ->orderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hayAlgunaActiva(int $tenantId): bool
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.tenantId = :tid AND c.activo = true')
            ->setParameter('tid', $tenantId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
