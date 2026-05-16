<?php

declare(strict_types=1);

namespace App\Module\Construccion\Repository;

use App\Module\Construccion\Entity\ConstruccionObra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConstruccionObra>
 */
class ConstruccionObraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConstruccionObra::class);
    }

    /** @return ConstruccionObra[] */
    public function findActivas(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.estado IN (:estados)')
            ->setParameter('estados', ['activa', 'pausada'])
            ->orderBy('o.fechaInicio', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return ConstruccionObra[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.cliente', 'c')
            ->addSelect('c')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<string, int> */
    public function countByEstado(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.estado, COUNT(o.id) AS total')
            ->groupBy('o.estado')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['estado']] = (int) $row['total'];
        }
        return $out;
    }

    public function getTotalGastosGlobal(): float
    {
        $result = $this->getEntityManager()
            ->createQuery('SELECT SUM(g.monto) FROM App\Module\Construccion\Entity\ConstruccionGasto g')
            ->getSingleScalarResult();
        return (float) ($result ?? 0);
    }
}
