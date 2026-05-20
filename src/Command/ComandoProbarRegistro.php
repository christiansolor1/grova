<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Tenant\RegistradorTenant;
use App\Service\Tenant\SolicitudRegistro;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'grova:probar-registro',
    description: '[TEST] Prueba el flujo completo de registro de tenant. Eliminar antes de producción.',
)]
final class ComandoProbarRegistro extends Command
{
    public function __construct(
        private readonly RegistradorTenant $registrador,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email del usuario de prueba', 'test@grovaapp.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $email = (string) $input->getOption('email');

        $io->title('Prueba de registro completo');

        $solicitud = new SolicitudRegistro(
            nombreEmpresa: 'Empresa de Prueba',
            nombre:        'Usuario',
            apellido:      'Test',
            email:         $email,
            contrasena:    'Test1234!',
        );

        $io->writeln('  Registrando tenant...');

        try {
            $usuario = $this->registrador->registrar($solicitud);
        } catch (\DomainException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $tenant = $usuario->getTenant();

        $io->success('Registro completado.');
        $io->definitionList(
            ['Email'       => $usuario->getEmail()],
            ['Tenant'      => $tenant?->getNombre()],
            ['Slug'        => $tenant?->getSlug()],
            ['BD creada'   => $tenant?->getDbName()],
            ['Rol'         => implode(', ', $usuario->getRoles())],
        );

        $io->note('Correo de bienvenida enviado a '.$email.' — revisa la bandeja.');

        return Command::SUCCESS;
    }
}
