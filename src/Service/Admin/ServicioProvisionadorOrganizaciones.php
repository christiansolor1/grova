<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\ModuloTenant;
use App\Entity\Plan;
use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ServicioProvisionadorOrganizaciones
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        private readonly EntityManagerInterface $emTenant,
        private readonly TenantContext $tenantContext,
        private readonly TenantRepository $repositorioInquilinos,
        private readonly PlanRepository $repositorioPlanes,
        private readonly UserRepository $repositorioUsuarios,
        private readonly UserPasswordHasherInterface $hasherContrasenas,
    ) {
    }

    /**
     * Crea tenant en core, BD dedicada, esquema de módulos, suscripción y opcionalmente admin del tenant.
     *
     * @param 'activo'|'suspendido' $estado
     */
    public function crear(
        string $nombre,
        int $idPlan,
        string $estado = 'activo',
        ?string $tipoCliente = null,
        ?string $emailAdmin = null,
        ?string $usernameAdmin = null,
        ?string $passwordAdmin = null,
        ?string $nombreAdmin = null,
        ?string $apellidoAdmin = null,
    ): Tenant {
        $nombreLimpio = trim($nombre);
        if ($nombreLimpio === '') {
            throw new \InvalidArgumentException('El nombre de la organización no puede estar vacío.');
        }

        if (!in_array($estado, ['activo', 'suspendido'], true)) {
            throw new \InvalidArgumentException('Estado de organización no válido.');
        }

        $plan = $this->repositorioPlanes->find($idPlan);
        if (!$plan instanceof Plan) {
            throw new \InvalidArgumentException('Plan no encontrado.');
        }

        $emailLimpio = $emailAdmin !== null ? trim($emailAdmin) : '';
        $usernameLimpio = $usernameAdmin !== null ? trim($usernameAdmin) : '';
        $passwordLimpia = $passwordAdmin ?? '';
        $quiereAdmin = $emailLimpio !== '' || $usernameLimpio !== '' || $passwordLimpia !== '';

        if ($quiereAdmin) {
            if ($emailLimpio === '' || $usernameLimpio === '' || $passwordLimpia === '') {
                throw new \InvalidArgumentException('Para crear un administrador inicial, email, usuario y contraseña son obligatorios.');
            }
            if ($this->repositorioUsuarios->findOneBy(['email' => $emailLimpio]) instanceof User) {
                throw new \InvalidArgumentException('Ya existe un usuario con ese email.');
            }
            if ($this->repositorioUsuarios->findOneBy(['username' => $usernameLimpio]) instanceof User) {
                throw new \InvalidArgumentException('Ya existe un usuario con ese nombre de usuario.');
            }
        }

        $slug = $this->generarSlugUnico($nombreLimpio);
        $tipoNormalizado = in_array($tipoCliente, ['cortesia', 'pago'], true) ? $tipoCliente : null;

        $inquilino = new Tenant();
        $inquilino->setNombre($nombreLimpio);
        $inquilino->setSlug($slug);
        $inquilino->setDbName($slug);
        $inquilino->setEstado($estado);
        $this->em->persist($inquilino);

        $suscripcion = new Suscripcion();
        $suscripcion->setTenant($inquilino);
        $suscripcion->setPlan($plan);
        $suscripcion->setFechaInicio(new \DateTimeImmutable('today'));
        $suscripcion->setFechaVencimiento(new \DateTimeImmutable('+1 year'));
        $suscripcion->setEstado('activa');
        $suscripcion->setTipoCliente($tipoNormalizado);
        $this->em->persist($suscripcion);

        foreach ($plan->getModulos() as $claveModulo) {
            $modulo = new ModuloTenant();
            $modulo->setTenant($inquilino);
            $modulo->setModuloKey($claveModulo);
            $modulo->setActivo(true);
            $this->em->persist($modulo);
        }

        if ($quiereAdmin) {
            $usuario = new User();
            $usuario->setEmail($emailLimpio);
            $usuario->setUsername($usernameLimpio);
            $usuario->setTenant($inquilino);
            $usuario->setRoles(['ROLE_ADMIN']);
            $usuario->setNombre($nombreAdmin !== null && trim($nombreAdmin) !== '' ? trim($nombreAdmin) : $nombreLimpio);
            $usuario->setApellido($apellidoAdmin !== null ? trim($apellidoAdmin) : '');
            $usuario->setPassword($this->hasherContrasenas->hashPassword($usuario, $passwordLimpia));
            $this->em->persist($usuario);
        }

        $this->em->flush();

        $this->provisionarBaseDatosTenant($inquilino);

        return $inquilino;
    }

    private function provisionarBaseDatosTenant(Tenant $inquilino): void
    {
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $inquilino->getDbName());
        if ($dbName === '') {
            throw new \RuntimeException('Nombre de base de datos del tenant no válido.');
        }

        $conexionCore = $this->em->getConnection();
        $conexionCore->executeStatement(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $dbName,
        ));

        $this->emTenant->getConnection()->close();
        $this->tenantContext->setTenant($inquilino, []);

        $herramientaEsquema = new SchemaTool($this->emTenant);
        $metadatos = $this->emTenant->getMetadataFactory()->getAllMetadata();

        if ($metadatos === []) {
            return;
        }

        $herramientaEsquema->createSchema($metadatos);
    }

    private function generarSlugUnico(string $nombre): string
    {
        $generadorSlug = new AsciiSlugger();
        $fragmentoBase = 'grova_'.strtolower((string) $generadorSlug->slug($nombre));
        $fragmentoBase = preg_replace('/[^a-z0-9_]/', '', $fragmentoBase) ?: 'grova_organizacion';
        $slug = $fragmentoBase;
        $sufijo = 1;

        while ($this->repositorioInquilinos->findOneBy(['slug' => $slug]) instanceof Tenant) {
            $slug = $fragmentoBase.'_'.$sufijo;
            ++$sufijo;
        }

        return $slug;
    }
}
