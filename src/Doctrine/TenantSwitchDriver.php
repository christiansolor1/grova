<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Service\TenantContext;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * Driver wrapper que cambia la base de datos activa justo después de conectar.
 * Ejecuta: USE `grova_{slug}` al abrir cada conexión al entity manager "tenant".
 */
final class TenantSwitchDriver extends AbstractDriverMiddleware
{
    public function __construct(
        \Doctrine\DBAL\Driver $driver,
        private readonly TenantContext $tenantContext,
    ) {
        parent::__construct($driver);
    }

    public function connect(array $params): Connection
    {
        $connection = parent::connect($params);

        $dbName = $this->tenantContext->getDbName();

        if ($dbName !== null) {
            // Sanitizamos el nombre: solo letras, números y guiones bajos
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
            $connection->exec('USE `' . $safe . '`');
        }

        return $connection;
    }
}
