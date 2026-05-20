<?php

declare(strict_types=1);

namespace App\Service\Setup;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Orquesta la instalación inicial: .env.local, BD, migraciones y usuario admin.
 */
final class InstaladorGrova
{
    public function __construct(
        private readonly EstadoInstalacion $estadoInstalacion,
        private readonly string $directorioProyecto,
    ) {
    }

    public function instalar(SolicitudInstalacion $solicitud): void
    {
        if ($this->estadoInstalacion->estaInstalado()) {
            throw new \RuntimeException('La instalación ya fue completada.');
        }

        $this->escribirEnvLocal($solicitud);

        try {
            $this->ejecutarConsola(['doctrine:database:create', '--if-not-exists', '--no-interaction']);
            $this->ejecutarConsola(['lexik:jwt:generate-keypair', '--skip-if-exists', '--no-interaction']);
            $this->ejecutarConsola(['doctrine:migrations:migrate', '--no-interaction']);
            $this->ejecutarConsola([
                'grova:instalacion:finalizar',
                '--espacio-trabajo='.$solicitud->nombreWorkspace,
                '--email='.$solicitud->emailAdmin,
                '--contrasena='.$solicitud->contrasenaAdmin,
                '--no-interaction',
            ]);
        } catch (\Throwable $e) {
            if (is_file($this->estadoInstalacion->obtenerRutaEnvLocal())) {
                unlink($this->estadoInstalacion->obtenerRutaEnvLocal());
            }

            throw $e;
        }
    }

    private function escribirEnvLocal(SolicitudInstalacion $solicitud): void
    {
        $secretoApp = bin2hex(random_bytes(32));
        $fraseJwt = bin2hex(random_bytes(32));
        $secretoMercure = bin2hex(random_bytes(32));
        $urlBaseDatos = $this->construirUrlBaseDatos($solicitud);

        $contenido = <<<ENV
# Generado por el instalador web de Grova — no editar manualmente los secretos sin saber el impacto.

###> symfony/framework-bundle ###
APP_ENV=dev
APP_DEBUG=1
APP_SECRET={$secretoApp}
APP_SHARE_DIR=var/share
APP_VERSION=0.1.0
APP_GIT_SHA=local
###< symfony/framework-bundle ###

###> symfony/routing ###
DEFAULT_URI=http://localhost
###< symfony/routing ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="{$urlBaseDatos}"
DATABASE_CORE_URL="{$urlBaseDatos}"
###< doctrine/doctrine-bundle ###

###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE={$fraseJwt}
###< lexik/jwt-authentication-bundle ###

###> nelmio/cors-bundle ###
CORS_ALLOW_ORIGIN='^https?://(localhost|127\\.0\\.0\\.1)(:[0-9]+)?$'
###< nelmio/cors-bundle ###

STORMGLASS_API_KEY=

###> symfony/mailer ###
MAILER_DSN=null://null
###< symfony/mailer ###
MAILER_FROM_EMAIL=noreply@grovaapp.com

###> symfony/mercure-bundle ###
MERCURE_URL=http://localhost/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost/.well-known/mercure
MERCURE_JWT_SECRET={$secretoMercure}
###< symfony/mercure-bundle ###

ENV;

        $ruta = $this->estadoInstalacion->obtenerRutaEnvLocal();

        if (false === file_put_contents($ruta, $contenido)) {
            throw new \RuntimeException('No se pudo escribir el archivo .env.local.');
        }
    }

    private function construirUrlBaseDatos(SolicitudInstalacion $solicitud): string
    {
        return sprintf(
            'mysql://%s:%s@%s:%d/%s?serverVersion=mariadb-10.11.16&charset=utf8mb4',
            rawurlencode($solicitud->usuarioBd),
            rawurlencode($solicitud->contrasenaBd),
            $solicitud->hostBd,
            $solicitud->puertoBd,
            rawurlencode($solicitud->nombreBd),
        );
    }

    /**
     * @param list<string> $argumentos
     */
    private function ejecutarConsola(array $argumentos): void
    {
        $proceso = new Process(
            array_merge(['php', 'bin/console'], $argumentos),
            $this->directorioProyecto,
            null,
            null,
            600,
        );

        $proceso->run();

        if (!$proceso->isSuccessful()) {
            throw new ProcessFailedException($proceso);
        }
    }
}
