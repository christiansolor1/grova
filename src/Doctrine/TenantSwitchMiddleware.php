<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Service\TenantContext;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Middleware DBAL que envuelve el driver de la conexión "tenant".
 * Al conectar, ejecuta USE `{db_name}` según el TenantContext activo.
 *
 * Se registra automáticamente como servicio Symfony (autowire).
 * Se declara en doctrine.yaml > dbal > connections > tenant > middlewares.
 */
final class TenantSwitchMiddleware implements Middleware
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return new TenantSwitchDriver($driver, $this->tenantContext);
    }
}
