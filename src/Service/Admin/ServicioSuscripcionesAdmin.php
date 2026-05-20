<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Plan;
use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Repository\PlanRepository;
use App\Repository\SuscripcionRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ServicioSuscripcionesAdmin
{
    public function __construct(
        private readonly TenantRepository $repositorioInquilinos,
        private readonly SuscripcionRepository $repositorioSuscripciones,
        private readonly PlanRepository $repositorioPlanes,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<Suscripcion>
     */
    public function listarHistorial(int $idTenant): array
    {
        $inquilino = $this->obtenerInquilino($idTenant);

        return $this->repositorioSuscripciones->findBy(
            ['tenant' => $inquilino],
            ['fechaInicio' => 'DESC'],
        );
    }

    public function obtenerSuscripcionActiva(int $idTenant): ?Suscripcion
    {
        $inquilino = $this->obtenerInquilino($idTenant);

        return $this->repositorioSuscripciones->findActivaForTenant($inquilino);
    }

    /**
     * @param positive-int $idTenant
     * @param positive-int $idPlan
     */
    public function asignarPlan(
        int $idTenant,
        int $idPlan,
        ?string $tipoCliente,
        ?\DateTimeImmutable $fechaVencimiento = null,
    ): Suscripcion {
        $inquilino = $this->obtenerInquilino($idTenant);

        $plan = $this->repositorioPlanes->find($idPlan);
        if (!$plan instanceof Plan) {
            throw new \InvalidArgumentException(sprintf('Plan con id %d no encontrado.', $idPlan));
        }

        // Vencer suscripción activa actual
        $activa = $this->repositorioSuscripciones->findActivaForTenant($inquilino);
        if ($activa instanceof Suscripcion) {
            $activa->setEstado('vencida');
        }

        $tipoNormalizado = in_array($tipoCliente, ['cortesia', 'pago'], true) ? $tipoCliente : null;

        $nueva = new Suscripcion();
        $nueva->setTenant($inquilino);
        $nueva->setPlan($plan);
        $nueva->setFechaInicio(new \DateTimeImmutable('today'));
        $nueva->setFechaVencimiento($fechaVencimiento ?? new \DateTimeImmutable('+1 year'));
        $nueva->setEstado('activa');
        $nueva->setTipoCliente($tipoNormalizado);

        $this->em->persist($nueva);
        $this->em->flush();

        return $nueva;
    }

    private function obtenerInquilino(int $id): Tenant
    {
        $inquilino = $this->repositorioInquilinos->find($id);

        if (!$inquilino instanceof Tenant) {
            throw new \InvalidArgumentException(sprintf('Empresa con id %d no encontrada.', $id));
        }

        return $inquilino;
    }
}
