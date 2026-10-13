<?php

declare(strict_types=1);

namespace App\Module\Residencial\Repository;

use App\Module\Residencial\Entity\Pase;
use App\Module\Residencial\Entity\Persona;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pase>
 */
class PaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pase::class);
    }

    public function findByToken(string $token): ?Pase
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.visitante', 'v')
            ->addSelect('v')
            ->leftJoin('p.casaDestino', 'c')
            ->addSelect('c')
            ->leftJoin('p.residente', 'r')
            ->addSelect('r')
            ->where('p.qrToken = :token')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Pase[] */
    public function findByResidente(Persona $residente, ?string $estado = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.visitante', 'v')
            ->addSelect('v')
            ->leftJoin('p.casaDestino', 'c')
            ->addSelect('c')
            ->where('p.residente = :residente')
            ->setParameter('residente', $residente)
            ->orderBy('p.creadoEn', 'DESC');

        if ($estado !== null) {
            $qb->andWhere('p.estado = :estado')->setParameter('estado', $estado);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return Pase[] Pases vencidos que siguen en estado pendiente */
    public function findVencidosPendientes(): array
    {
        $hoy = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('p')
            ->where('p.estado = :estado')
            ->andWhere('p.fechaFin IS NOT NULL')
            ->andWhere('p.fechaFin < :hoy')
            ->setParameter('estado', 'pendiente')
            ->setParameter('hoy', $hoy)
            ->getQuery()
            ->getResult();
    }
}
