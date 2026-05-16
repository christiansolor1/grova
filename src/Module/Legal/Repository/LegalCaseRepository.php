<?php

declare(strict_types=1);

namespace App\Module\Legal\Repository;

use App\Module\Legal\Entity\LegalCase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<LegalCase> */
class LegalCaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalCase::class);
    }

    /** @return LegalCase[] */
    public function findAllWithContact(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.contact', 'co')
            ->addSelect('co')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return LegalCase[] */
    public function findAbiertos(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.contact', 'co')
            ->addSelect('co')
            ->where('c.estado IN (:estados)')
            ->setParameter('estados', ['abierto', 'en_proceso'])
            ->orderBy('c.fechaApertura', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByEstado(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.estado, COUNT(c.id) as total')
            ->groupBy('c.estado')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['estado']] = (int) $row['total'];
        }
        return $result;
    }

    public function getTotalHonorariosPendientes(): float
    {
        // Suma de honorarios de casos abiertos menos lo cobrado
        $cases = $this->findAbiertos();
        return array_sum(array_map(fn($c) => $c->getSaldoPendiente(), $cases));
    }
}
