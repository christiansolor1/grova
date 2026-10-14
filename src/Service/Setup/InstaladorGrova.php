<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\Entity\ModuloTenant;
use App\Entity\Plan;
use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Orquesta la instalación inicial: .env.local, BD, migraciones y usuario admin.
 * Todo en PHP directo — sin subprocesos — igual que WordPress.
 */
final class InstaladorGrova
{
    public function __construct(
        private readonly EstadoInstalacion $estadoInstalacion,
        private readonly string $directorioProyecto,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasherContrasenas,
        private readonly PlanRepository $repositorioPlanes,
        private readonly UserRepository $repositorioUsuarios,
    ) {
    }

    public function instalar(SolicitudInstalacion $solicitud): void
    {
        if ($this->estadoInstalacion->estaInstalado()) {
            throw new \RuntimeException('La instalación ya fue completada.');
        }

        $urlBd = $this->construirUrlBaseDatos($solicitud);

        // 1. Verificar conexión antes de escribir nada
        $conexion = $this->conectar($solicitud);

        try {
            // 2. Crear BD si no existe — quoteIdentifier previene SQL injection
            $nombreBd = $conexion->quoteIdentifier($solicitud->nombreBd);
            $conexion->executeStatement(
                "CREATE DATABASE IF NOT EXISTS {$nombreBd} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            $conexion->close();

            // 3. Escribir .env.local
            $fraseJwt = $this->escribirEnvLocal($solicitud, $urlBd);

            // 4. Generar JWT keypair
            $this->generarJwtKeypair($fraseJwt);

            // 5. Correr migraciones usando la conexión del EM actual
            $this->correrMigraciones($urlBd);

            // 6. Crear tenant y superusuario
            $this->crearTenantYAdmin($solicitud);

        } catch (\Throwable $e) {
            if (is_file($this->estadoInstalacion->obtenerRutaEnvLocal())) {
                unlink($this->estadoInstalacion->obtenerRutaEnvLocal());
            }
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function conectar(SolicitudInstalacion $solicitud): \Doctrine\DBAL\Connection
    {
        $conexion = DriverManager::getConnection([
            'driver'   => 'pdo_mysql',
            'host'     => $solicitud->hostBd,
            'port'     => $solicitud->puertoBd,
            'user'     => $solicitud->usuarioBd,
            'password' => $solicitud->contrasenaBd,
            'charset'  => 'utf8mb4',
        ]);

        $conexion->connect();

        return $conexion;
    }

    private function escribirEnvLocal(SolicitudInstalacion $solicitud, string $urlBd): string
    {
        $secretoApp   = bin2hex(random_bytes(32));
        $fraseJwt     = bin2hex(random_bytes(32));
        $secretoMercure = bin2hex(random_bytes(32));

        $contenido = <<<ENV
# Generado por el instalador web de Grova.

###> symfony/framework-bundle ###
APP_ENV=prod
APP_DEBUG=0
APP_SECRET={$secretoApp}
APP_SHARE_DIR=var/share
APP_VERSION=0.1.0
APP_GIT_SHA=local
###< symfony/framework-bundle ###

###> symfony/routing ###
DEFAULT_URI=http://localhost
###< symfony/routing ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="{$urlBd}"
DATABASE_CORE_URL="{$urlBd}"
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
            throw new \RuntimeException('No se pudo escribir el archivo .env.local. Verifica permisos del directorio.');
        }

        chmod($ruta, 0600);

        return $fraseJwt;
    }

    private function generarJwtKeypair(string $fraseJwt): void
    {
        $dirJwt     = $this->directorioProyecto.'/config/jwt';
        $rutaPriv   = $dirJwt.'/private.pem';
        $rutaPub    = $dirJwt.'/public.pem';

        if (!is_dir($dirJwt)) {
            mkdir($dirJwt, 0755, true);
        }

        if (is_file($rutaPriv) && is_file($rutaPub)) {
            return;
        }

        $privKey = openssl_pkey_new([
            'digest_alg'       => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => \OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privKey === false) {
            throw new \RuntimeException('No se pudo generar el par de claves JWT.');
        }

        openssl_pkey_export($privKey, $privPem, $fraseJwt);
        $detalles = openssl_pkey_get_details($privKey);
        $pubPem   = $detalles['key'];

        file_put_contents($rutaPriv, $privPem);
        file_put_contents($rutaPub, $pubPem);
        chmod($rutaPriv, 0600);
    }

    private function correrMigraciones(string $urlBd): void
    {
        $conexionMigraciones = DriverManager::getConnection([
            'url' => $urlBd,
        ]);

        $configuracion = new \Doctrine\Migrations\Configuration\Configuration();
        $configuracion->addMigrationsDirectory(
            'DoctrineMigrations',
            $this->directorioProyecto.'/migrations'
        );
        $configuracion->setMetadataStorageConfiguration(
            new \Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration()
        );
        $configuracion->setTransactional(false);

        $fabricaDependencias = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuracion),
            new ExistingConnection($conexionMigraciones)
        );

        $migradorPlan = $fabricaDependencias->getMigrationPlanCalculator();
        $plan = $migradorPlan->getPlanUntilVersion(
            $fabricaDependencias->getVersionAliasResolver()->resolveVersionAlias('latest')
        );

        $migrador = $fabricaDependencias->getMigrator();
        $migrador->migrate($plan, new MigratorConfiguration());

        $conexionMigraciones->close();
    }

    private function crearTenantYAdmin(SolicitudInstalacion $solicitud): void
    {
        if ($this->repositorioUsuarios->findOneBy(['email' => $solicitud->emailAdmin]) instanceof User) {
            return;
        }

        $slugger    = new AsciiSlugger();
        $slug       = 'grova_'.strtolower((string) $slugger->slug($solicitud->nombreWorkspace));
        $slug       = preg_replace('/[^a-z0-9_]/', '', $slug) ?: 'grova_workspace';

        $inquilino = new Tenant();
        $inquilino->setNombre($solicitud->nombreWorkspace);
        $inquilino->setSlug($slug);
        $inquilino->setDbName($slug);
        $inquilino->setEstado('activo');
        $this->em->persist($inquilino);

        $plan = $this->repositorioPlanes->findOneBy(['nombre' => 'Pro'])
            ?? $this->repositorioPlanes->findOneBy(['nombre' => 'Free']);

        if ($plan instanceof Plan) {
            $suscripcion = new Suscripcion();
            $suscripcion->setTenant($inquilino);
            $suscripcion->setPlan($plan);
            $suscripcion->setFechaInicio(new \DateTimeImmutable('today'));
            $suscripcion->setFechaVencimiento(new \DateTimeImmutable('+1 year'));
            $suscripcion->setEstado('activa');
            $this->em->persist($suscripcion);

            foreach ($plan->getModulos() as $claveModulo) {
                $modulo = new ModuloTenant();
                $modulo->setTenant($inquilino);
                $modulo->setModuloKey($claveModulo);
                $modulo->setActivo(true);
                $this->em->persist($modulo);
            }
        }

        $parteLocal = strstr($solicitud->emailAdmin, '@', true);
        $partes     = is_string($parteLocal) ? preg_split('/[._-]+/', $parteLocal) : [];
        $nombre     = ucfirst((string) ($partes[0] ?? $solicitud->nombreWorkspace));

        $usuario = new User();
        $usuario->setEmail($solicitud->emailAdmin);
        $usuario->setUsername($solicitud->usernameAdmin);
        $usuario->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_DEVELOPER']);
        $usuario->setTenant($inquilino);
        $usuario->setNombre($nombre);
        $usuario->setApellido('');
        $usuario->setPassword($this->hasherContrasenas->hashPassword($usuario, $solicitud->contrasenaAdmin));
        $this->em->persist($usuario);

        $this->em->flush();
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
}
