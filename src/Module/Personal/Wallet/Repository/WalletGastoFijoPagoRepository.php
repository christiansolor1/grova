<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Repository;

use App\Module\Personal\Wallet\Entity\WalletGastoFijo;
use App\Module\Personal\Wallet\Entity\WalletGastoFijoPago;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WalletGastoFijoPagoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletGastoFijoPago::class);
    }

    /**
     * Todos los pagos de un gasto en un mes/año dado.
     * @return WalletGastoFijoPago[]
     */
    public function findPagosMesGasto(WalletGastoFijo $gasto, int $anio, int $mes): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.gastoFijo = :g AND p.anio = :a AND p.mes = :m')
            ->setParameter('g', $gasto)
            ->setParameter('a', $anio)
            ->setParameter('m', $mes)
            ->orderBy('p.fecha', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Todos los pagos de un gasto, ordenados por año/mes ascendente.
     * @return WalletGastoFijoPago[]
     */
    public function findAllByGasto(WalletGastoFijo $gasto): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.gastoFijo = :g')
            ->setParameter('g', $gasto)
            ->orderBy('p.anio', 'ASC')
            ->addOrderBy('p.mes', 'ASC')
            ->addOrderBy('p.fecha', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve un mapa [gastoFijoId => WalletGastoFijoPago[]] para un mes/año dado.
     * @param WalletGastoFijo[] $gastos
     * @return array<int, WalletGastoFijoPago[]>
     */
    public function findPagosMes(array $gastos, int $anio, int $mes): array
    {
        if (empty($gastos)) {
            return [];
        }

        $pagos = $this->createQueryBuilder('p')
            ->where('p.gastoFijo IN (:gastos) AND p.anio = :a AND p.mes = :m')
            ->setParameter('gastos', $gastos)
            ->setParameter('a', $anio)
            ->setParameter('m', $mes)
            ->orderBy('p.fecha', 'ASC')
            ->getQuery()
            ->getResult();

        $mapa = [];
        foreach ($pagos as $pago) {
            $mapa[$pago->getGastoFijo()->getId()][] = $pago;
        }

        return $mapa;
    }
}
