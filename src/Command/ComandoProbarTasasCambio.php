<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ExchangeRateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'grova:tasas:probar',
    description: 'Muestra tasas de cambio por banco (útil si Atlántida no aparece en /work).',
)]
final class ComandoProbarTasasCambio extends Command
{
    public function __construct(
        private readonly ExchangeRateService $tasasCambio,
        private readonly CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limpiar-cache', null, InputOption::VALUE_NONE, 'Borra la caché de tasas antes de consultar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('limpiar-cache')) {
            $this->cache->delete(ExchangeRateService::claveCacheTasas());
            $io->note('Caché de tasas eliminada.');
        }

        $r = $this->tasasCambio->getRates();
        $io->title('Tasas de cambio — ' . ($r['fecha'] ?? '?'));
        $io->text('Fuente principal (EUR): ' . ($r['source'] ?? '—'));

        $filas = [];
        foreach ($r['banks'] as $nombre => $b) {
            $filas[] = [
                $nombre,
                $b['usd'] . ' / ' . $b['usd_venta'],
                $b['eur'] > 0 ? $b['eur'] . ' / ' . $b['eur_venta'] : '—',
                $b['url'] ?? '',
            ];
        }
        $io->table(['Banco', 'USD compra/venta', 'EUR compra/venta', 'URL'], $filas);

        if (!isset($r['banks']['Atlántida'])) {
            $io->error('Atlántida no está en la respuesta. Revisa conectividad a basa-static.s3.amazonaws.com desde este PHP.');
            $io->text('La web pública de Banco Atlántida usa el mismo JSON: https://basa-static.s3.amazonaws.com/tasa-de-cambio.json');

            return Command::FAILURE;
        }

        $io->success('Atlántida OK. Mejor EUR: ' . $r['best_eur'] . ' · Mejor USD: ' . $r['best_usd']);

        return Command::SUCCESS;
    }
}
