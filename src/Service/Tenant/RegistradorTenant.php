<?php

declare(strict_types=1);

namespace App\Service\Tenant;

use App\Entity\ModuloTenant;
use App\Entity\Plan;
use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\TenantRepository;
use App\Service\Auth\ServicioVerificacionEmail;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class RegistradorTenant
{
    public function __construct(
        private readonly EntityManagerInterface $emCore,
        private readonly Connection $conexionCore,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly PlanRepository $repositorioPlanes,
        private readonly TenantRepository $repositorioTenants,
        private readonly ServicioVerificacionEmail $verificacionEmail,
    ) {
    }

    public function registrar(SolicitudRegistro $solicitud): User
    {
        if ($this->emCore->getRepository(User::class)->findOneBy(['email' => $solicitud->email])) {
            throw new \DomainException('Este email ya tiene una cuenta.');
        }

        $slug = $this->sanitizarSlug($solicitud->slug);

        if ($this->repositorioTenants->findOneBy(['slug' => $slug])) {
            throw new \DomainException('Este nombre de workspace no está disponible. Elige otro.');
        }

        $plan = $this->repositorioPlanes->findOneBy(['nombre' => 'Trial', 'estado' => 'activo'])
            ?? throw new \RuntimeException('No existe un plan Trial activo. Contacta al administrador.');

        $tenant = new Tenant();
        $tenant->setNombre($solicitud->nombreEmpresa)
               ->setSlug($slug)
               ->setDbName($slug)
               ->setEstado('activo');
        $this->emCore->persist($tenant);

        $suscripcion = new Suscripcion();
        $suscripcion->setTenant($tenant)
                    ->setPlan($plan)
                    ->setFechaInicio(new \DateTimeImmutable('today'))
                    ->setFechaVencimiento(new \DateTimeImmutable('+30 days'))
                    ->setEstado('activa')
                    ->setTipoCliente('cortesia');
        $this->emCore->persist($suscripcion);

        foreach ($plan->getModulos() as $claveModulo) {
            $modulo = new ModuloTenant();
            $modulo->setTenant($tenant)
                   ->setModuloKey($claveModulo)
                   ->setActivo(true);
            $this->emCore->persist($modulo);
        }

        // El username es el slug visible (sin prefijo grova_) para permitir login con él
        $slugVisible = (string) preg_replace('/^grova_/', '', $slug, 1);

        $usuario = new User();
        $usuario->setEmail($solicitud->email)
                ->setUsername($slugVisible)
                ->setNombre($solicitud->nombre)
                ->setApellido($solicitud->apellido)
                ->setRoles(['ROLE_TENANT_ADMIN'])
                ->setTenant($tenant)
                ->setEmailVerificado(false);
        $usuario->setPassword($this->hasher->hashPassword($usuario, $solicitud->contrasena));
        $this->emCore->persist($usuario);

        $this->emCore->flush();

        $this->crearBaseDatosTenant($slug);

        // Envía verificación de email (incluye bienvenida)
        $this->verificacionEmail->enviarVerificacion($usuario);

        return $usuario;
    }

    public function slugDisponible(string $slug): bool
    {
        $slug = $this->sanitizarSlug($slug);

        return $slug !== '' && !$this->repositorioTenants->findOneBy(['slug' => $slug]);
    }

    public function sanitizarSlug(string $slug): string
    {
        $slugger = new AsciiSlugger();
        $limpio  = strtolower((string) $slugger->slug($slug));
        $limpio  = preg_replace('/[^a-z0-9-]/', '', $limpio) ?? '';
        $limpio  = trim($limpio, '-');

        return $limpio !== '' ? 'grova_'.$limpio : '';
    }

    private function crearBaseDatosTenant(string $nombreBd): void
    {
        $this->conexionCore->executeStatement(
            sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $nombreBd,
            )
        );
    }
}
