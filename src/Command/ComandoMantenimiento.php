<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\ErrorLogRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Mantenimiento general de la plataforma: limpia datos obsoletos y optimiza la BD.
 *
 * Uso manual:   php bin/console grova:mantenimiento
 *               php bin/console grova:mantenimiento --dry-run
 * Cron semanal: 0 3 * * 0 php /var/www/grova/bin/console grova:mantenimiento --no-interaction >> /var/log/grova-cron.log 2>&1
 */
#[AsCommand(
    name: 'grova:mantenimiento',
    description: 'Limpieza general de la plataforma: tokens, notificaciones, logs y errores antiguos.',
)]
final class ComandoMantenimiento extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationRepository $repoNotificaciones,
        private readonly ErrorLogRepository $repoErrores,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Solo mostrar lo que se haría sin modificar nada')
            ->addOption('dias-notificaciones', null, InputOption::VALUE_REQUIRED, 'Días para eliminar notificaciones descartadas', '90')
            ->addOption('dias-errores', null, InputOption::VALUE_REQUIRED, 'Días para eliminar errores resueltos/ignorados', '90');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $diasNotif = (int) $input->getOption('dias-notificaciones');
        $diasErrores = (int) $input->getOption('dias-errores');

        $io->title('Mantenimiento de Grova');
        $dryRun && $io->note('Modo simulación — no se modificará nada.');

        // ── 1. Limpiar tokens de sesión revocados ──
        $io->section('1. Tokens de sesión revocados');

        $userRepo = $this->em->getRepository(User::class);
        $users = $userRepo->findAll();
        $totalTokens = 0;

        foreach ($users as $user) {
            $antes = count($user->getRevokedSessionTokens());
            if ($antes === 0) {
                continue;
            }
            $user->limpiarRevokedSessionTokens(0);
            $totalTokens += $antes;
            $io->text(sprintf('  %s: %d token(s) limpiados', (string) $user->getEmail(), $antes));
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success(sprintf('Tokens limpiados: %d (en %d usuario(s))', $totalTokens, count($users)));

        // ── 2. Limpiar notificaciones descartadas antiguas ──
        $io->section(sprintf('2. Notificaciones descartadas (+%d días)', $diasNotif));

        $notifEliminadas = $this->repoNotificaciones->deleteOldDismissed($diasNotif);

        if ($dryRun) {
            $io->text(sprintf('  %d notificación(es) serían eliminadas.', $notifEliminadas));
        } else {
            $io->success(sprintf('Notificaciones eliminadas: %d', $notifEliminadas));
        }

        // ── 3. Limpiar error_logs resueltos/ignorados antiguos ──
        $io->section(sprintf('3. Error logs resueltos/ignorados (+%d días)', $diasErrores));

        $erroresEliminados = $this->repoErrores->deleteOldResolved($diasErrores);

        if ($dryRun) {
            $io->text(sprintf('  %d error(es) serían eliminados.', $erroresEliminados));
        } else {
            $io->success(sprintf('Error logs eliminados: %d', $erroresEliminados));
        }

        // ── Resumen ──
        $io->section('Resumen');
        $io->table(
            ['Tarea', 'Resultado'],
            [
                ['Tokens revocados', "{$totalTokens}"],
                ['Notificaciones viejas', $dryRun ? "{$notifEliminadas} (simulado)" : "{$notifEliminadas}"],
                ['Error logs viejos', $dryRun ? "{$erroresEliminados} (simulado)" : "{$erroresEliminados}"],
            ]
        );

        if ($dryRun) {
            $io->warning('Modo simulación — ejecuta sin --dry-run para aplicar.');
        } else {
            $io->success('Mantenimiento completado.');
        }

        return Command::SUCCESS;
    }
}
