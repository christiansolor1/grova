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
        $ruta = $this->directorioProyecto.'/.env.local';

        return is_file($ruta) && filesize($ruta) > 0;
    }

    public function obtenerRutaEnvLocal(): string
    {
        return $this->directorioProyecto.'/.env.local';
    }
}
