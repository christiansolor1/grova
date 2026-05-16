<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // El $context de Runtime es principalmente $_SERVER; en muchos entornos PATH está definido
    // y entonces no se mezcla $_ENV — si APP_DEBUG solo viviera en $_ENV, faltaría y (bool) null
    // desactivaría el modo debug (sin barra WDT, sin X-Debug-Token). Completamos con $_ENV.
    $context += $_ENV;

    $env   = $context['APP_ENV']  ?? 'dev';
    $debug = $context['APP_DEBUG'] ?? '1';
    if (!\is_bool($debug)) {
        $debug = filter_var($debug, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE) ?? (bool) $debug;
    }

    return new Kernel($env, $debug);
};
