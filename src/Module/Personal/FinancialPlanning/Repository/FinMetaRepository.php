<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinMeta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinMetaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinMeta::class);
    }

    /** @return FinMeta[] */
    public function findByTenant(int $tenantId, bool $soloActivas = false): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('m.activa', 'DESC')
            ->addOrderBy('m.fechaObjetivo', 'ASC')
            ->addOrderBy('m.creadoAt', 'ASC');

        if ($soloActivas) {
            $qb->andWhere('m.activa = true');
        }

        return $qb->getQuery()->getResult();
    }
}
