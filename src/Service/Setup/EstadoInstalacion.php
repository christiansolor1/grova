<?php

declare(strict_types=1);

namespace App\Service\Setup;

/**
 * Indica si la instalación inicial ya se completó (.env.local presente).
 */
final class EstadoInstalacion
{
    public function __construct(
        private readonly string $directorioProyecto,
    ) {
    }

    public function estaInstalado(): bool
    {
        return is_file($this->directorioProyecto.'/.env.local');
    }

    public function obtenerRutaEnvLocal(): string
    {
        return $this->directorioProyecto.'/.env.local';
    }
}
