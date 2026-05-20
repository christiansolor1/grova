<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Mail\CorreoRecordatorioSuscripcion;
use App\Repository\SuscripcionRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Envía recordatorios a los usuarios cuyas suscripciones están próximas a vencer.
 *
 * Uso manual:   php bin/console grova:suscripciones:recordar
 *               php bin/console grova:suscripciones:recordar --dias=7 --dry-run
 * Cron diario:  0 8 * * * php /var/www/grova/bin/console grova:suscripciones:recordar --no-interaction >> /var/log/grova-cron.log 2>&1
 */
#[AsCommand(
    name: 'grova:suscripciones:recordar',
    description: 'Notifica a los usuarios sobre suscripciones próximas a vencer (in-app + email).',
)]
final class ComandoRecordarVencimientos extends Command
{
    /** Umbrales de días para enviar recordatorio. */
    private const UMBRALES = [15, 7, 3, 1];

    public function __construct(
        private readonly SuscripcionRepository $repositorioSuscripciones,
        private readonly UserRepository $repositorioUsuarios,
        private readonly NotificationService $notificaciones,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dias', null, InputOption::VALUE_REQUIRED, 'Solo enviar recordatorios para este umbral de días', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Solo listar lo que se haría sin enviar nada');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $diasPersonalizado = $input->getOption('dias');
        $umbrales = $diasPersonalizado !== null ? [(int) $diasPersonalizado] : self::UMBRALES;
        $dryRun = (bool) $input->getOption('dry-run');

        $totalNotificado = 0;
        $totalSuscripciones = 0;

        foreach ($umbrales as $dias) {
            $proximas = $this->repositorioSuscripciones->findProximasAVencer($dias);

            if ($proximas === []) {
                continue;
            }

            $totalSuscripciones += count($proximas);

            foreach ($proximas as $suscripcion) {
                $tenant = $suscripcion->getTenant();
                $diasRestantes = (int) $suscripcion->getFechaVencimiento()->diff(new \DateTimeImmutable('today'))->days;

                // Si el vencimiento es hoy, mostrar "Hoy" en lugar de 0
                $diasDisplay = $diasRestantes === 0 ? 0 : $diasRestantes;

                if ($dryRun) {
                    $io->note(sprintf(
                        '[DRY-RUN] %s — vence %s (%d día(s)) — notificar a %d usuario(s)',
                        $tenant->getNombre(),
                        $suscripcion->getFechaVencimiento()->format('d/m/Y'),
                        $diasDisplay,
                        $tenant->getSlug() !== null ? count($this->repositorioUsuarios->findByTenant($tenant)) : 0,
                    ));
                    continue;
                }

                $usuarios = $this->repositorioUsuarios->findByTenant($tenant);

                foreach ($usuarios as $usuario) {
                    $this->notificar($usuario, $suscripcion->getPlan()->getNombre(), $tenant->getNombre(), $suscripcion->getFechaVencimiento()->format('d/m/Y'), $diasDisplay);
                }

                $suscripcion->setUltimoRecordatorioEnviadoAt(new \DateTimeImmutable());
                $totalNotificado += count($usuarios);
            }

            $this->em->flush();
        }

        if ($dryRun) {
            $io->success(sprintf(
                'Simulación: %d suscripción(es) próximas a vencer recibirían recordatorio.',
                $totalSuscripciones,
            ));

            return Command::SUCCESS;
        }

        if ($totalSuscripciones === 0) {
            $io->success('No hay suscripciones próximas a vencer.');

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Recordatorios enviados: %d notificación(es) para %d suscripción(es).',
            $totalNotificado,
            $totalSuscripciones,
        ));

        return Command::SUCCESS;
    }

    private function notificar(User $usuario, string $planNombre, string $tenantNombre, string $fechaVencimiento, int $diasRestantes): void
    {
        // Notificación in-app
        $this->notificaciones->notify(
            user: $usuario,
            title: 'Suscripción próxima a vencer',
            body: sprintf(
                'El plan %s de %s vence el %s (%d día%s).',
                $planNombre,
                $tenantNombre,
                $fechaVencimiento,
                $diasRestantes,
                $diasRestantes === 1 ? '' : 's',
            ),
            url: '/es/profile',
            module: 'core',
            icon: 'bi-clock',
            type: 'warning',
        );

        // Correo electrónico
        try {
            $this->mailer->send(new CorreoRecordatorioSuscripcion(
                usuario: $usuario,
                tenantNombre: $tenantNombre,
                fechaVencimiento: $fechaVencimiento,
                diasRestantes: $diasRestantes,
            ));
        } catch (\Throwable $e) {
            // El error en el correo no debe impedir el proceso
        }
    }
}
