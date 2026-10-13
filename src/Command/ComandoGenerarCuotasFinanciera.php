<?php

declare(strict_types=1);

namespace App\Command;

use App\Module\Personal\FinancialPlanning\Service\FinCuotaGeneradorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'grova:financiera:generar-cuotas',
    description: 'Genera las cuotas del mes para todos los compromisos activos de un tenant.',
)]
final class ComandoGenerarCuotasFinanciera extends Command
{
    public function __construct(
        private readonly FinCuotaGeneradorService $generador,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('tenant_id', InputArgument::REQUIRED, 'ID del tenant')
            ->addOption('anio', null, InputOption::VALUE_OPTIONAL, 'Año (default: año actual)')
            ->addOption('mes',  null, InputOption::VALUE_OPTIONAL, 'Mes 1-12 (default: mes actual)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $tenantId = (int) $input->getArgument('tenant_id');
        $anio     = (int) ($input->getOption('anio') ?: date('Y'));
        $mes      = (int) ($input->getOption('mes')  ?: date('n'));

        $io->title(sprintf('Generando cuotas — tenant %d — %04d/%02d', $tenantId, $anio, $mes));

        $resultado = $this->generador->generarMes($tenantId, $anio, $mes);

        foreach ($resultado['detalle'] as $linea) {
            $io->writeln('  ' . $linea);
        }

        $io->success(sprintf('%d cuotas creadas, %d omitidas (ya existían).', $resultado['creadas'], $resultado['omitidas']));

        return Command::SUCCESS;
    }
}
