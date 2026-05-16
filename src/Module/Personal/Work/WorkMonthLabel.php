<?php

declare(strict_types=1);

namespace App\Module\Personal\Work;

/**
 * Etiqueta "mes año" según locale (es/en) para Work: selects, facturas, PDF, flashes.
 */
final class WorkMonthLabel
{
    private const MESES_ES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    private const MESES_EN = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    public static function format(int $year, int $month, string $locale): string
    {
        $month = max(1, min(12, $month));
        $lang  = self::primaryLanguage($locale);

        return ($lang === 'es' ? self::MESES_ES[$month] : self::MESES_EN[$month]) . ' ' . $year;
    }

    private static function primaryLanguage(string $locale): string
    {
        $locale = strtolower(str_replace('_', '-', explode('.', $locale)[0]));
        $parts   = explode('-', $locale, 2);

        return $parts[0] === 'es' ? 'es' : 'en';
    }
}
