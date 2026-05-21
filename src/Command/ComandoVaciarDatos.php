<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'grova:vaciar-datos',
    description: 'Vacía todas las tablas de datos (excepto planes y migraciones). Para empezar desde cero.',
)]
final class ComandoVaciarDatos extends Command
{
    /** Tablas que NO se deben truncar */
    private const TABLAS_PROTEGIDAS = [
        'plan',
        'doctrine_migration_versions',
        'messenger_messages',
        'migration_versions',
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Confirmar la operación destructiva');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            $io->error('Esta operación borrará TODOS los datos. Usa --force para confirmar.');
            return Command::FAILURE;
        }

        $dbName = $this->connection->getDatabase();
        $io->warning(sprintf('Vaciando base de datos: %s', $dbName));

        $tablas = $this->connection->executeQuery('SHOW TABLES')->fetchFirstColumn();

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        $vaciadas = [];
        $omitidas = [];

        foreach ($tablas as $tabla) {
            if (in_array($tabla, self::TABLAS_PROTEGIDAS, true)) {
                $omitidas[] = $tabla;
                continue;
            }

            $this->connection->executeStatement(sprintf('TRUNCATE TABLE `%s`', $tabla));
            $vaciadas[] = $tabla;
        }

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $io->success(sprintf('%d tabla(s) vaciadas.', count($vaciadas)));
        $io->table(['Tabla vaciada'], array_map(fn ($t) => [$t], $vaciadas));

        if ($omitidas) {
            $io->note(sprintf('Protegidas (no tocadas): %s', implode(', ', $omitidas)));
        }

        return Command::SUCCESS;
    }
}
