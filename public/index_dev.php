<?php

declare(strict_types=1);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context): Kernel {
    // Entrada de desarrollo forzada para pruebas locales.
    // Si no es localhost, no permitimos acceso por seguridad.
    $remoteAddr = (string) ($context['REMOTE_ADDR'] ?? '');
    $allowedIps = ['127.0.0.1', '::1'];
    if (!\in_array($remoteAddr, $allowedIps, true)) {
        http_response_code(403);
        exit('Forbidden');
    }

    $context += $_ENV;
    $context['APP_ENV'] = 'dev';
    $context['APP_DEBUG'] = true;

    return new Kernel('dev', true);
};
