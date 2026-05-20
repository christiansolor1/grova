<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Datos agregados para el panel Super Admin: plan activo, suscripción y conteo de usuarios.
     *
     * @return list<array{
     *     id: int,
     *     nombre: string,
     *     slug: string,
     *     estado: string,
     *     tipo: string|null,
     *     totalUsuarios: int,
     *     nombrePlan: string|null,
     *     estadoSuscripcion: string|null,
     *     fechaVencimiento: \DateTimeImmutable|null,
     * }>
     */
    public function obtenerDatosListadoAdmin(): array
    {
        /** @var list<array<string, mixed>> $filas */
        $filas = $this->createQueryBuilder('t')
            ->select([
                't.id AS id',
                't.nombre AS nombre',
                't.slug AS slug',
                't.estado AS estado',
                't.tipo AS tipo',
                'COUNT(DISTINCT u.id) AS totalUsuarios',
                'p.nombre AS nombrePlan',
                's.estado AS estadoSuscripcion',
                's.fechaVencimiento AS fechaVencimiento',
            ])
            ->leftJoin(User::class, 'u', 'WITH', 'u.tenant = t')
            ->leftJoin(Suscripcion::class, 's', 'WITH', 's.tenant = t AND s.estado IN (:estadosSuscripcion)')
            ->leftJoin('s.plan', 'p')
            ->setParameter('estadosSuscripcion', ['activa', 'vencida'])
            ->groupBy('t.id, t.nombre, t.slug, t.estado, t.tipo, p.nombre, s.estado, s.fechaVencimiento')
            ->orderBy('t.nombre', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $resultado = [];
        foreach ($filas as $fila) {
            $vencimiento = $fila['fechaVencimiento'];
            $resultado[] = [
                'id' => (int) $fila['id'],
                'nombre' => (string) $fila['nombre'],
                'slug' => (string) $fila['slug'],
                'estado' => (string) $fila['estado'],
                'tipo' => isset($fila['tipo']) ? (string) $fila['tipo'] : null,
                'totalUsuarios' => (int) $fila['totalUsuarios'],
                'nombrePlan' => isset($fila['nombrePlan']) ? (string) $fila['nombrePlan'] : null,
                'estadoSuscripcion' => isset($fila['estadoSuscripcion']) ? (string) $fila['estadoSuscripcion'] : null,
                'fechaVencimiento' => $vencimiento instanceof \DateTimeImmutable ? $vencimiento : null,
            ];
        }

        return $resultado;
    }
}
