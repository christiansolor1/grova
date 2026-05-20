<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\SuscripcionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Marca como vencidas las suscripciones activas cuya fecha de vencimiento ya pasó.
 *
 * Uso manual:   php bin/console grova:suscripciones:vencer
 * Cron diario:  0 0 * * * php /var/www/grova/bin/console grova:suscripciones:vencer --no-interaction >> /var/log/grova-cron.log 2>&1
 */
#[AsCommand(
    name: 'grova:suscripciones:vencer',
    description: 'Marca como vencidas las suscripciones activas cuya fecha de vencimiento ya pasó.',
)]
final class ComandoVencerSuscripciones extends Command
{
    public function __construct(
        private readonly SuscripcionRepository $repositorioSuscripciones,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $vencidas = $this->repositorioSuscripciones->findVencidasPendientes();

        if ($vencidas === []) {
            $io->success('No hay suscripciones para vencer.');

            return Command::SUCCESS;
        }

        foreach ($vencidas as $suscripcion) {
            $suscripcion->setEstado('vencida');
        }

        $this->em->flush();

        $io->success(sprintf(
            '%d suscripción(es) marcadas como vencidas.',
            count($vencidas),
        ));

        return Command::SUCCESS;
    }
}
