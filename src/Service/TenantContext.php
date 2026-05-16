<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant;

/**
 * Servicio de alcance request que guarda el tenant activo y sus módulos.
 * Se popula en cada request por TenantContextSubscriber (web) o JWTAuthenticatedSubscriber (API).
 */
final class TenantContext
{
    private ?Tenant $tenant = null;

    /** @var list<string> */
    private array $modulosActivos = [];

    /**
     * @param list<string> $modulos
     */
    public function setTenant(Tenant $tenant, array $modulos = []): void
    {
        $this->tenant = $tenant;
        $this->modulosActivos = $modulos;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getTenantSlug(): ?string
    {
        return $this->tenant?->getSlug();
    }

    public function getDbName(): ?string
    {
        return $this->tenant?->getDbName();
    }

    /** @return list<string> */
    public function getModulosActivos(): array
    {
        return $this->modulosActivos;
    }

    public function hasModulo(string $key): bool
    {
        return \in_array($key, $this->modulosActivos, true);
    }
}
