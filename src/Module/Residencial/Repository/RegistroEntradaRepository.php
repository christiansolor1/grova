<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\RegistroEntrada;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RegistroEntrada>
 */
class RegistroEntradaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistroEntrada::class);
    }

    /** @return RegistroEntrada[] */
    public function findUltimasEntradas(int $tenantId, int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.visitante', 'v')
            ->addSelect('v')
            ->leftJoin('r.pase', 'p')
            ->addSelect('p')
            ->leftJoin('p.casaDestino', 'c')
            ->addSelect('c')
            ->where('r.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('r.horaEntrada', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return RegistroEntrada[] */
    public function findByRango(int $tenantId, \DateTimeImmutable $desde, \DateTimeImmutable $hasta): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.visitante', 'v')
            ->addSelect('v')
            ->where('r.tenantId = :tid')
            ->andWhere('r.horaEntrada BETWEEN :desde AND :hasta')
            ->setParameter('tid', $tenantId)
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->orderBy('r.horaEntrada', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countEntradasHoy(int $tenantId): int
    {
        $hoy    = new \DateTimeImmutable('today');
        $manana = $hoy->modify('+1 day');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.tenantId = :tid')
            ->andWhere('r.horaEntrada >= :hoy')
            ->andWhere('r.horaEntrada < :manana')
            ->setParameter('tid', $tenantId)
            ->setParameter('hoy', $hoy)
            ->setParameter('manana', $manana)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return RegistroEntrada[] Visitas activas (sin hora de salida) */
    public function findVisitantesActivos(int $tenantId): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.visitante', 'v')
            ->addSelect('v')
            ->where('r.tenantId = :tid')
            ->andWhere('r.horaSalida IS NULL')
            ->setParameter('tid', $tenantId)
            ->orderBy('r.horaEntrada', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
