<?php

declare(strict_types=1);

namespace App\Module\Legal\Repository;

use App\Module\Legal\Entity\LegalCase;
use App\Module\Legal\Entity\LegalFollowUp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<LegalFollowUp> */
class LegalFollowUpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalFollowUp::class);
    }

    /** @return LegalFollowUp[] */
    public function findByCase(LegalCase $case): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.case = :case')
            ->setParameter('case', $case)
            ->orderBy('f.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Próximas audiencias (futuras) */
    /** @return LegalFollowUp[] */
    public function findProximasAudiencias(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.case', 'c')
            ->addSelect('c')
            ->where('f.proximaAudiencia >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('f.proximaAudiencia', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
