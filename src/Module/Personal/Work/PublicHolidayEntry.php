<?php

declare(strict_types=1);

namespace App\Module\Personal\Work;

/**
 * Feriado calculado (no persistido) para el panel Work — misma forma de uso que WorkHoliday en Twig.
 */
final class PublicHolidayEntry
{
    public function __construct(
        private readonly \DateTimeImmutable $fecha,
        private readonly string $nombre,
    ) {
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }
}
