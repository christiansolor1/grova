<?php

declare(strict_types=1);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context): Kernel {
    // Entrada de producción forzada para pruebas de comportamiento.
    $context += $_ENV;
    $context['APP_ENV'] = 'prod';
    $context['APP_DEBUG'] = false;

    return new Kernel('prod', false);
};
