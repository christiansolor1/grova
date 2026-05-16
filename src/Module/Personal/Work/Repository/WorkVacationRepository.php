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

    public function countDaysUsedInYear(int $year): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('SUM(v.dias)')
            ->where('v.anio = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDaysUsedInSemestre(int $year, int $semestre): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('SUM(v.dias)')
            ->where('v.anio = :year')
            ->andWhere('v.semestre = :semestre')
            ->setParameter('year', $year)
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return WorkVacation[] */
    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.anio = :year')
            ->setParameter('year', $year)
            ->orderBy('v.fechaInicio', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
