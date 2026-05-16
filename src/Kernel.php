<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Symfony 7.4 no expone framework.default_timezone; PHP en UTC desplaza «hoy» vs Honduras.
        $tz = $_ENV['DEFAULT_TIMEZONE'] ?? 'America/Tegucigalpa';
        try {
            new \DateTimeZone($tz);
        } catch (\Exception) {
            $tz = 'UTC';
        }
        date_default_timezone_set($tz);

        parent::boot();
    }
}
