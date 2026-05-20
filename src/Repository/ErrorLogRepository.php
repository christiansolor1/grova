<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ErrorLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ErrorLog>
 */
class ErrorLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ErrorLog::class);
    }

    /**
     * @param array{level?: string, status?: string, channel?: string, tenant_id?: int, desde?: string, hasta?: string} $filtros
     * @return array{items: ErrorLog[], total: int, pagina: int, paginas: int}
     */
    public function listar(array $filtros = [], int $pagina = 1, int $porPagina = 30): array
    {
        $qb = $this->crearQueryListado($filtros);

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(e.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        if ($porPagina <= 0) {
            return [
                'items' => $qb->getQuery()->getResult(),
                'total' => $total,
                'pagina' => 1,
                'paginas' => 1,
            ];
        }

        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = max(1, min($pagina, $paginas));

        $items = $qb->setFirstResult(($pagina - 1) * $porPagina)
            ->setMaxResults($porPagina)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    /**
     * @param array{level?: string, status?: string, channel?: string, tenant_id?: int, desde?: string, hasta?: string} $filtros
     */
    private function crearQueryListado(array $filtros): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC');

        if (!empty($filtros['level'])) {
            $qb->andWhere('e.level = :level')->setParameter('level', $filtros['level']);
        }
        if (!empty($filtros['status'])) {
            $qb->andWhere('e.status = :status')->setParameter('status', $filtros['status']);
        }
        if (!empty($filtros['channel'])) {
            $qb->andWhere('e.channel = :channel')->setParameter('channel', $filtros['channel']);
        }
        if (!empty($filtros['tenant_id'])) {
            $qb->andWhere('e.tenantId = :tenant_id')->setParameter('tenant_id', $filtros['tenant_id']);
        }
        if (!empty($filtros['desde'])) {
            $qb->andWhere('e.createdAt >= :desde')->setParameter('desde', new \DateTimeImmutable($filtros['desde']));
        }
        if (!empty($filtros['hasta'])) {
            $hastaFin = new \DateTimeImmutable($filtros['hasta'].' 23:59:59');
            $qb->andWhere('e.createdAt <= :hasta')->setParameter('hasta', $hastaFin);
        }

        return $qb;
    }

    /** @return string[] */
    public function findDistinctLevels(): array
    {
        return $this->createQueryBuilder('e')
            ->select('DISTINCT e.level')
            ->orderBy('e.level', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /** @return string[] */
    public function findDistinctChannels(): array
    {
        return $this->createQueryBuilder('e')
            ->select('DISTINCT e.channel')
            ->orderBy('e.channel', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Elimina logs resueltos o ignorados más antiguos que $dias días.
     *
     * @return int Cantidad de registros eliminados
     */
    public function deleteOldResolved(int $dias = 90): int
    {
        $limite = new \DateTimeImmutable("-{$dias} days");

        return (int) $this->createQueryBuilder('e')
            ->delete()
            ->where('e.status IN (:statuses)')
            ->andWhere('e.createdAt < :limite')
            ->setParameter('statuses', [ErrorLog::STATUS_RESOLVED, ErrorLog::STATUS_IGNORED])
            ->setParameter('limite', $limite)
            ->getQuery()
            ->execute();
    }
}
