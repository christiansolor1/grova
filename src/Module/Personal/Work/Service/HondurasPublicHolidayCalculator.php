<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Service;

use App\Module\Personal\Work\PublicHolidayEntry;

/**
 * Feriados nacionales usados en Work (Honduras), fechas observadas por año.
 *
 * Semana Morazánica (Soldado, Raza, Fuerzas Armadas): tres días corridos a partir del
 * primer miércoles de octubre (patrón que coincide con 2025 y 2026 respecto a calendarios públicos).
 * Día de Choluteca: 1 de octubre (fijo).
 *
 * Semana Santa: Jueves Santo y Viernes Santo según Pascua occidental (easter_date).
 */
final class HondurasPublicHolidayCalculator
{
    /**
     * @return list<PublicHolidayEntry> Ordenados por fecha y nombre.
     */
    public function holidaysForYear(int $year): array
    {
        if ($year < 1970 || $year > 2100) {
            return [];
        }

        $tz = new \DateTimeZone(date_default_timezone_get());
        $list = [];

        $add = static function (string $ymd, string $nombre) use (&$list, $tz): void {
            $list[] = new PublicHolidayEntry(
                new \DateTimeImmutable($ymd . ' 12:00:00', $tz),
                $nombre,
            );
        };

        $add(sprintf('%d-01-01', $year), 'Año Nuevo');
        $add(sprintf('%d-01-06', $year), 'Día de los Reyes Magos');
        $add(sprintf('%d-02-03', $year), 'Nuestra Señora de Suyapa');

        $easterSun = $this->easterSundayUtc($year)->setTimezone($tz)->setTime(12, 0, 0);
        $maundy    = $easterSun->modify('-3 days');
        $goodFri   = $easterSun->modify('-2 days');
        $list[] = new PublicHolidayEntry($maundy, 'Jueves Santo');
        $list[] = new PublicHolidayEntry($goodFri, 'Viernes Santo');

        $add(sprintf('%d-04-14', $year), 'Día de las Américas');
        $add(sprintf('%d-05-01', $year), 'Día del Trabajo');
        $add(sprintf('%d-09-15', $year), 'Día de la Independencia');

        $add(sprintf('%d-10-01', $year), 'Día de Choluteca');

        $firstWedOct = $this->firstWednesdayOfOctober($year, $tz);
        $soldado     = $firstWedOct;
        $raza        = $firstWedOct->modify('+1 day');
        $fuerzas     = $firstWedOct->modify('+2 days');
        $list[] = new PublicHolidayEntry($soldado, 'Día del Soldado');
        $list[] = new PublicHolidayEntry($raza, 'Día de la Raza');
        $list[] = new PublicHolidayEntry($fuerzas, 'Día de las Fuerzas Armadas');

        $add(sprintf('%d-11-01', $year), 'Día de Todos los Santos');
        $add(sprintf('%d-12-25', $year), 'Navidad');

        usort($list, static function (PublicHolidayEntry $a, PublicHolidayEntry $b): int {
            $cmp = $a->getFecha() <=> $b->getFecha();
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a->getNombre(), $b->getNombre());
        });

        return $list;
    }

    private function easterSundayUtc(int $year): \DateTimeImmutable
    {
        if (!\function_exists('easter_date')) {
            throw new \RuntimeException('La extensión calendar (easter_date) es necesaria para calcular la Semana Santa.');
        }
        $ts = easter_date($year);
        if ($ts === false) {
            throw new \InvalidArgumentException('Año no válido para Pascua: ' . $year);
        }

        return (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone('UTC'));
    }

    private function firstWednesdayOfOctober(int $year, \DateTimeZone $tz): \DateTimeImmutable
    {
        $d = new \DateTimeImmutable(sprintf('%d-10-01 12:00:00', $year), $tz);
        while ((int) $d->format('N') !== 3) {
            $d = $d->modify('+1 day');
        }

        return $d;
    }
}
