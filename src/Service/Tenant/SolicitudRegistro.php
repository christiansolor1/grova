<?php

declare(strict_types=1);

namespace App\Service\Tenant;

final readonly class SolicitudRegistro
{
    public function __construct(
        public string $nombreEmpresa,
        public string $nombre,
        public string $apellido,
        public string $email,
        public string $contrasena,
        public string $slug,
    ) {
    }
}
