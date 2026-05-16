<?php

declare(strict_types=1);

namespace App\Module\Construccion\Repository;

use App\Module\Construccion\Entity\ConstruccionGasto;
use App\Module\Construccion\Entity\ConstruccionObra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConstruccionGasto>
 */
class ConstruccionGastoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConstruccionGasto::class);
    }

    /** @return ConstruccionGasto[] */
    public function findByObra(ConstruccionObra $obra): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.proveedor', 'p')
            ->addSelect('p')
            ->where('g.obra = :obra')
            ->setParameter('obra', $obra)
            ->orderBy('g.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<string, float> totals by categoria for an obra */
    public function totalsByCategoriaForObra(ConstruccionObra $obra): array
    {
        $rows = $this->createQueryBuilder('g')
            ->select('g.categoria, SUM(g.monto) AS total')
            ->where('g.obra = :obra')
            ->setParameter('obra', $obra)
            ->groupBy('g.categoria')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['categoria']] = (float) $row['total'];
        }
        return $out;
    }
}
