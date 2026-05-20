<?php

// Router para php built-in server
// Uso: php -S 0.0.0.0:8080 router.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Servir archivos estáticos directamente
$filePath = __DIR__ . '/public' . $uri;
if ($uri !== '/' && is_file($filePath)) {
    $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript', 'css' => 'text/css',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'pem' => 'application/x-pem-file',
        'json' => 'application/json', 'txt' => 'text/plain',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile($filePath);
    return true;
}

// autoload_runtime.php usa SCRIPT_FILENAME para bootear Symfony.
// Apuntamos a index.php para que funcione correctamente.
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

// Symfony se encarga del resto
require __DIR__ . '/public/index.php';
