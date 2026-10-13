<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Repository;

use App\Module\Personal\Wallet\Entity\WalletCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletCategory>
 */
class WalletCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletCategory::class);
    }

    public function findDuplicate(int $tenantId, string $nombre, string $color, ?int $excludeId = null): ?WalletCategory
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.tenantId = :tid')
            ->andWhere('(LOWER(c.nombre) = LOWER(:nombre) OR c.color = :color)')
            ->setParameter('tid', $tenantId)
            ->setParameter('nombre', $nombre)
            ->setParameter('color', $color)
            ->setMaxResults(1);

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Devuelve todas las categorías ordenadas: raíces primero (tipo + nombre),
     * luego sus hijos agrupados inmediatamente después. Eager-load del padre.
     * @return WalletCategory[]
     */
    public function findAllOrdered(?int $tenantId = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p')
            ->addSelect('p');

        if ($tenantId !== null) {
            $qb->where('c.tenantId = :tid')->setParameter('tid', $tenantId);
        }

        $all = $qb->orderBy('c.tipo', 'ASC')
                   ->addOrderBy('c.nombre', 'ASC')
                   ->getQuery()
                   ->getResult();

        // Agrupar en PHP: cada raíz seguida de sus hijos
        $raices   = array_values(array_filter($all, fn($c) => $c->getParent() === null));
        $hijos    = array_values(array_filter($all, fn($c) => $c->getParent() !== null));

        $result = [];
        foreach ($raices as $raiz) {
            $result[] = $raiz;
            foreach ($hijos as $hijo) {
                if ($hijo->getParent()->getId() === $raiz->getId()) {
                    $result[] = $hijo;
                }
            }
        }
        // Huérfanos (padre eliminado — por si acaso)
        foreach ($hijos as $hijo) {
            if (!in_array($hijo, $result, true)) {
                $result[] = $hijo;
            }
        }

        return $result;
    }

    /** Solo categorías raíz (sin padre) de un tipo dado */
    public function findRaices(int $tenantId, string $tipo): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.tenantId = :tid')
            ->andWhere('c.tipo = :tipo')
            ->andWhere('c.parent IS NULL')
            ->setParameter('tid', $tenantId)
            ->setParameter('tipo', $tipo)
            ->orderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
