<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\EstadoSubscripcion;
use App\Entity\User;
use App\Repository\SuscripcionRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Decide si un usuario tiene acceso al sistema según su suscripción o licencia.
 *
 * Orden de verificación:
 *  1. Super Admin → acceso libre siempre.
 *  2. LICENSE_KEY en entorno → instalación on-premise, valida licencia firmada.
 *  3. Sin LICENSE_KEY → instalación SaaS, valida suscripción en BD.
 */
final class GuardiaSubscripcion
{
    public function __construct(
        private readonly SuscripcionRepository $repositorioSuscripciones,
        private readonly ValidadorLicencias $validadorLicencias,
        private readonly Security $security,
        private readonly string $licenseKey,
    ) {
    }

    public function verificar(?User $usuario): EstadoSubscripcion
    {
        if ($usuario === null) {
            return EstadoSubscripcion::activa();
        }

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return EstadoSubscripcion::superAdmin();
        }

        // Tenants tipo staff (desarrolladores internos) bypass
        $tenant = $usuario->getTenant();
        if ($tenant !== null && $tenant->esStaff()) {
            return EstadoSubscripcion::superAdmin();
        }

        // Instalación on-premise: validar con licencia firmada
        if ($this->licenseKey !== '') {
            return $this->validadorLicencias->esValida($this->licenseKey)
                ? EstadoSubscripcion::activa()
                : EstadoSubscripcion::vencida();
        }

        // Instalación SaaS: validar contra BD
        if ($tenant === null) {
            return EstadoSubscripcion::sinSuscripcion();
        }

        $suscripcion = $this->repositorioSuscripciones->findActivaForTenant($tenant);

        if ($suscripcion === null) {
            return EstadoSubscripcion::sinSuscripcion();
        }

        if ($suscripcion->getFechaVencimiento() < new \DateTimeImmutable('today')) {
            return EstadoSubscripcion::vencida();
        }

        return EstadoSubscripcion::activa();
    }
}
