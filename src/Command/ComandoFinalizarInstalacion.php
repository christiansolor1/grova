<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ModuloTenant;
use App\Entity\Plan;
use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'grova:instalacion:finalizar',
    description: 'Crea el tenant principal y el usuario superadmin tras la instalación web.',
)]
final class ComandoFinalizarInstalacion extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasherContrasenas,
        private readonly PlanRepository $repositorioPlanes,
        private readonly UserRepository $repositorioUsuarios,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('espacio-trabajo', null, InputOption::VALUE_REQUIRED, 'Nombre del workspace / empresa')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email del superusuario')
            ->addOption('contrasena', null, InputOption::VALUE_REQUIRED, 'Contraseña del superusuario');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $espacioTrabajo = trim((string) $input->getOption('espacio-trabajo'));
        $correo = trim((string) $input->getOption('email'));
        $contrasena = (string) $input->getOption('contrasena');

        if ($espacioTrabajo === '' || $correo === '' || $contrasena === '') {
            $io->error('Faltan opciones obligatorias (--espacio-trabajo, --email, --contrasena).');

            return Command::FAILURE;
        }

        if ($this->repositorioUsuarios->findOneBy(['email' => $correo]) instanceof User) {
            $io->warning(sprintf('El usuario %s ya existe; se omite la creación.', $correo));

            return Command::SUCCESS;
        }

        $slug = $this->construirSlugUnico($espacioTrabajo);
        $nombreBdInquilino = $slug;

        $inquilino = new Tenant();
        $inquilino->setNombre($espacioTrabajo);
        $inquilino->setSlug($slug);
        $inquilino->setDbName($nombreBdInquilino);
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

        $usuario = new User();
        $usuario->setEmail($correo);
        $usuario->setUsername($correo);
        $usuario->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_DEVELOPER']);
        $usuario->setTenant($inquilino);
        $usuario->setNombre($this->extraerPrimerNombre($espacioTrabajo, $correo));
        $usuario->setApellido('');
        $usuario->setPassword($this->hasherContrasenas->hashPassword($usuario, $contrasena));
        $this->em->persist($usuario);

        $this->em->flush();

        $io->success(sprintf('Tenant «%s» (%s) y superusuario %s creados.', $espacioTrabajo, $slug, $correo));

        return Command::SUCCESS;
    }

    private function construirSlugUnico(string $nombreWorkspace): string
    {
        $generadorSlug = new AsciiSlugger();
        $fragmentoBase = 'grova_'.strtolower((string) $generadorSlug->slug($nombreWorkspace));
        $fragmentoBase = preg_replace('/[^a-z0-9_]/', '', $fragmentoBase) ?: 'grova_workspace';
        $slug = $fragmentoBase;
        $sufijo = 1;

        while ($this->em->getRepository(Tenant::class)->findOneBy(['slug' => $slug]) instanceof Tenant) {
            $slug = $fragmentoBase.'_'.$sufijo;
            ++$sufijo;
        }

        return $slug;
    }

    private function extraerPrimerNombre(string $espacioTrabajo, string $correo): string
    {
        $parteLocal = strstr($correo, '@', true);

        if (is_string($parteLocal) && $parteLocal !== '') {
            $partes = preg_split('/[._-]+/', $parteLocal);

            return ucfirst($partes[0] ?? $parteLocal);
        }

        return $espacioTrabajo;
    }
}
