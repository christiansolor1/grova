<?php

declare(strict_types=1);

namespace App\Command;

use App\Module\Personal\Wallet\Entity\WalletCategory;
use App\Module\Personal\Wallet\Repository\WalletCategoryRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'grova:sembrar-categorias-wallet',
    description: 'Crea las categorías base de Wallet para un tenant.',
)]
final class ComandoSembrarCategoriasWallet extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WalletCategoryRepository $categoryRepo,
        private readonly TenantContext $tenantContext,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tenant_id', InputArgument::REQUIRED, 'ID del tenant');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $tenantId = (int) $input->getArgument('tenant_id');

        $categorias = [
            // ── Gastos ────────────────────────────────────────────────────────
            ['nombre' => 'Hogar',            'tipo' => 'gasto',   'icono' => 'fa-solid fa-house',         'color' => '#f97316'],
            ['nombre' => 'Alimentación',     'tipo' => 'gasto',   'icono' => 'fa-solid fa-utensils',      'color' => '#22c55e'],
            ['nombre' => 'Transporte',       'tipo' => 'gasto',   'icono' => 'fa-solid fa-car',           'color' => '#3b82f6'],
            ['nombre' => 'Gasolina',         'tipo' => 'gasto',   'icono' => 'fa-solid fa-gas-pump',      'color' => '#f59e0b'],
            ['nombre' => 'Salud',            'tipo' => 'gasto',   'icono' => 'fa-solid fa-heart-pulse',   'color' => '#ef4444'],
            ['nombre' => 'Celular',          'tipo' => 'gasto',   'icono' => 'fa-solid fa-mobile-screen', 'color' => '#6366f1'],
            ['nombre' => 'Entretenimiento',  'tipo' => 'gasto',   'icono' => 'fa-solid fa-film',          'color' => '#a855f7'],
            ['nombre' => 'Pesca',            'tipo' => 'gasto',   'icono' => 'fa-solid fa-fish',          'color' => '#06b6d4'],
            ['nombre' => 'Ropa',             'tipo' => 'gasto',   'icono' => 'bi-bag',                    'color' => '#ec4899'],
            ['nombre' => 'Mascotas',         'tipo' => 'gasto',   'icono' => 'fa-solid fa-dog',           'color' => '#d97706'],
            ['nombre' => 'Otros gastos',     'tipo' => 'gasto',   'icono' => 'bi-tag',                    'color' => '#64748b'],
            // ── Ingresos ──────────────────────────────────────────────────────
            ['nombre' => 'Trabajo nocturno', 'tipo' => 'ingreso', 'icono' => 'bi-moon',                   'color' => '#8b5cf6'],
            ['nombre' => 'Freelance',        'tipo' => 'ingreso', 'icono' => 'fa-solid fa-laptop',        'color' => '#10b981'],
            ['nombre' => 'Otros ingresos',   'tipo' => 'ingreso', 'icono' => 'bi-cash-coin',              'color' => '#14b8a6'],
        ];

        $creadas = 0;
        $omitidas = 0;

        foreach ($categorias as $datos) {
            $existe = $this->categoryRepo->findOneBy([
                'tenantId' => $tenantId,
                'nombre'   => $datos['nombre'],
            ]);

            if ($existe !== null) {
                $io->writeln('  <comment>Omitida (ya existe):</comment> ' . $datos['nombre']);
                $omitidas++;
                continue;
            }

            $cat = new WalletCategory();
            $cat->setTenantId($tenantId);
            $cat->setNombre($datos['nombre']);
            $cat->setTipo($datos['tipo']);
            $cat->setIcono($datos['icono']);
            $cat->setColor($datos['color']);
            $this->em->persist($cat);
            $io->writeln('  <info>Creada:</info> ' . $datos['nombre'] . ' (' . $datos['tipo'] . ')');
            $creadas++;
        }

        $this->em->flush();

        $io->success(sprintf('%d categorías creadas, %d omitidas.', $creadas, $omitidas));

        return Command::SUCCESS;
    }
}
