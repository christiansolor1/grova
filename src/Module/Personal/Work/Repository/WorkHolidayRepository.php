<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Repository;

use App\Module\Personal\Work\Entity\WorkHoliday;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WorkHoliday> */
class WorkHolidayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkHoliday::class);
    }

    /** @return WorkHoliday[] */
    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.anio = :year')
            ->setParameter('year', $year)
            ->orderBy('h.fecha', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Devuelve las fechas de feriados del año como strings Y-m-d */
    public function findDatesForYear(int $year): array
    {
        $holidays = $this->findByYear($year);

        return array_map(
            fn(WorkHoliday $h) => $h->getFecha()->format('Y-m-d'),
            $holidays
        );
    }
}
