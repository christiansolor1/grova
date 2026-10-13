<?php

declare(strict_types=1);

namespace App\Module\Personal\Agenda\Repository;

use App\Module\Personal\Agenda\Entity\AgendaEvento;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AgendaEvento> */
class AgendaEventoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgendaEvento::class);
    }

    /** @return AgendaEvento[] */
    public function findByTenant(int $tenantId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.tenantId = :tenantId')
            ->setParameter('tenantId', $tenantId)
            ->orderBy('e.fecha', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve eventos en los próximos $dias días.
     * Para eventos recurrentes se compara mes/día contra el rango calculado desde hoy.
     *
     * @return AgendaEvento[]
     */
    public function findProximos(int $tenantId, int $dias = 30): array
    {
        $todos = $this->findByTenant($tenantId);
        $hoy   = new \DateTimeImmutable('today');
        $limite = $hoy->modify("+{$dias} days");

        return array_values(array_filter($todos, static function (AgendaEvento $evento) use ($hoy, $limite): bool {
            if (!$evento->isRecurrente()) {
                $f = $evento->getFecha();
                return $f >= $hoy && $f <= $limite;
            }

            $anioActual = (int) $hoy->format('Y');
            $mes        = (int) $evento->getFecha()->format('m');
            $dia        = (int) $evento->getFecha()->format('d');

            $candidato = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $anioActual, $mes, $dia));
            if ($candidato === false) {
                return false;
            }
            if ($candidato < $hoy) {
                $candidato = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $anioActual + 1, $mes, $dia));
                if ($candidato === false) {
                    return false;
                }
            }

            return $candidato >= $hoy && $candidato <= $limite;
        }));
    }

    /** @return AgendaEvento[] */
    public function findByMes(int $tenantId, int $mes, int $anio): array
    {
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = (new \DateTimeImmutable($desde))->modify('last day of this month')->format('Y-m-d');

        $directos = $this->createQueryBuilder('e')
            ->where('e.tenantId = :tenantId')
            ->andWhere('e.fecha BETWEEN :desde AND :hasta')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->orderBy('e.fecha', 'ASC')
            ->getQuery()
            ->getResult();

        $recurrentes = $this->createQueryBuilder('e')
            ->where('e.tenantId = :tenantId')
            ->andWhere('e.recurrente = true')
            ->andWhere('MONTH(e.fecha) = :mes')
            ->setParameter('tenantId', $tenantId)
            ->setParameter('mes', $mes)
            ->orderBy('e.fecha', 'ASC')
            ->getQuery()
            ->getResult();

        $merged = array_merge($directos, $recurrentes);
        $unique = [];
        $ids    = [];
        foreach ($merged as $e) {
            if ($e->getId() !== null && !in_array($e->getId(), $ids, true)) {
                $ids[]    = $e->getId();
                $unique[] = $e;
            }
        }

        usort($unique, static fn (AgendaEvento $a, AgendaEvento $b): int => (int) $a->getFecha()->format('d') <=> (int) $b->getFecha()->format('d'));

        return $unique;
    }
}
