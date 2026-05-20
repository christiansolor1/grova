<?php

declare(strict_types=1);

namespace App\Service\Setup;

final readonly class SolicitudInstalacion
{
    public function __construct(
        public string $hostBd,
        public int $puertoBd,
        public string $nombreBd,
        public string $usuarioBd,
        public string $contrasenaBd,
        public string $emailAdmin,
        public string $contrasenaAdmin,
        public string $nombreWorkspace,
    ) {
    }

    /**
     * @param array<string, mixed> $datos
     */
    public static function desdeFormulario(array $datos): self
    {
        $host = trim((string) ($datos['db_host'] ?? '127.0.0.1'));
        $puerto = (int) ($datos['db_port'] ?? 3306);

        if (str_contains($host, ':')) {
            [$host, $puertoDesdeHost] = explode(':', $host, 2);
            $host = trim($host);
            if (is_numeric($puertoDesdeHost)) {
                $puerto = (int) $puertoDesdeHost;
            }
        }

        return new self(
            hostBd: $host,
            puertoBd: $puerto > 0 ? $puerto : 3306,
            nombreBd: trim((string) ($datos['db_name'] ?? '')),
            usuarioBd: trim((string) ($datos['db_user'] ?? '')),
            contrasenaBd: (string) ($datos['db_password'] ?? ''),
            emailAdmin: trim((string) ($datos['admin_email'] ?? '')),
            contrasenaAdmin: (string) ($datos['admin_password'] ?? ''),
            nombreWorkspace: trim((string) ($datos['workspace_name'] ?? '')),
        );
    }
}
