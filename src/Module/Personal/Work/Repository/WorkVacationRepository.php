<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Repository;

use App\Module\Personal\Work\Entity\WorkVacation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WorkVacation> */
class WorkVacationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkVacation::class);
    }

    public function countDaysUsedInYear(int $tenantId, int $year): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('SUM(v.dias)')
            ->where('v.tenantId = :tenantId')
            ->andWhere('v.anio = :year')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDaysUsedInSemestre(int $tenantId, int $year, int $semestre): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('SUM(v.dias)')
            ->where('v.tenantId = :tenantId')
            ->andWhere('v.anio = :year')
            ->andWhere('v.semestre = :semestre')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('year', $year)
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return WorkVacation[] */
    public function findByYear(int $tenantId, int $year): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.tenantId = :tenantId')
            ->andWhere('v.anio = :year')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('year', $year)
            ->orderBy('v.fechaInicio', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
