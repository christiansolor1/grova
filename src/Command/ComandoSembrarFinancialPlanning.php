<?php

declare(strict_types=1);

namespace App\Command;

use App\Module\Personal\FinancialPlanning\Entity\FinAcreedor;
use App\Module\Personal\FinancialPlanning\Entity\FinBolsa;
use App\Module\Personal\FinancialPlanning\Entity\FinCompromiso;
use App\Module\Personal\FinancialPlanning\Repository\FinAcreedorRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinBolsaRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinCompromisoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'grova:sembrar-financial-planning',
    description: 'Crea bolsas, compromisos y acreedores iniciales del módulo Planificación Financiera.',
)]
final class ComandoSembrarFinancialPlanning extends Command
{
    public function __construct(
        private readonly EntityManagerInterface    $em,
        private readonly FinBolsaRepository        $bolsaRepo,
        private readonly FinCompromisoRepository   $compromisoRepo,
        private readonly FinAcreedorRepository     $acreedorRepo,
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

        $io->title('Planificación Financiera — Siembra inicial');

        // ── 1. BOLSAS ──────────────────────────────────────────────────────────
        $io->section('Bolsas');
        $bolsasDef = [
            ['nombre' => 'Necesidades',  'tipo' => 'necesidad',  'porcentaje' => 70.00, 'orden' => 1, 'color' => '#3b82f6'],
            ['nombre' => 'Deseos',       'tipo' => 'gusto',      'porcentaje' => 10.00, 'orden' => 2, 'color' => '#f97316'],
            ['nombre' => 'Ahorro',       'tipo' => 'ahorro',     'porcentaje' => 10.00, 'orden' => 3, 'color' => '#22c55e'],
            ['nombre' => 'Deudas',       'tipo' => 'deuda',      'porcentaje' => 20.00, 'orden' => 4, 'color' => '#ef4444'],
            ['nombre' => 'Inversiones',  'tipo' => 'inversion',  'porcentaje' =>  0.00, 'orden' => 5, 'color' => '#8b5cf6'],
            ['nombre' => 'Familia',      'tipo' => 'familia',    'porcentaje' =>  0.00, 'orden' => 6, 'color' => '#ec4899'],
        ];

        $bolsas = [];
        foreach ($bolsasDef as $def) {
            $existe = $this->bolsaRepo->findOneBy(['tenantId' => $tenantId, 'nombre' => $def['nombre']]);
            if ($existe !== null) {
                $bolsas[$def['tipo']] = $existe;
                $io->writeln('  <comment>Omitida (ya existe):</comment> ' . $def['nombre']);
                continue;
            }
            $bolsa = new FinBolsa();
            $bolsa->setTenantId($tenantId);
            $bolsa->setNombre($def['nombre']);
            $bolsa->setTipo($def['tipo']);
            $bolsa->setPorcentajeSugerido($def['porcentaje']);
            $bolsa->setOrden($def['orden']);
            $bolsa->setColor($def['color']);
            $this->em->persist($bolsa);
            $bolsas[$def['tipo']] = $bolsa;
            $io->writeln('  <info>Creada:</info> ' . $def['nombre']);
        }
        $this->em->flush();

        // ── 2. COMPROMISOS FIJOS ───────────────────────────────────────────────
        $io->section('Compromisos fijos');

        /**
         * Compromisos reales del tenant grova_christian.
         * monto_cuota en HNL, dia_pago = día del mes en que vence.
         */
        $compromisosDef = [
            // NECESIDADES
            [
                'nombre'    => 'Alquiler casa',
                'tipo'      => FinCompromiso::TIPO_SERVICIO,
                'bolsa'     => 'necesidad',
                'monto'     => 3000.00,
                'dia'       => 1,
                'frecuencia' => FinCompromiso::FRECUENCIA_MENSUAL,
            ],
            [
                'nombre'    => 'Energía Eléctrica (EEH)',
                'tipo'      => FinCompromiso::TIPO_SERVICIO,
                'bolsa'     => 'necesidad',
                'monto'     => 2500.00,
                'dia'       => 10,
                'frecuencia' => FinCompromiso::FRECUENCIA_MENSUAL,
            ],
            [
                'nombre'    => 'Agua (SANAA)',
                'tipo'      => FinCompromiso::TIPO_SERVICIO,
                'bolsa'     => 'necesidad',
                'monto'     => 250.00,
                'dia'       => 15,
                'frecuencia' => FinCompromiso::FRECUENCIA_MENSUAL,
            ],
            [
                'nombre'    => 'Cable Color (internet + cable)',
                'tipo'      => FinCompromiso::TIPO_SUSCRIPCION,
                'bolsa'     => 'necesidad',
                'monto'     => 1050.00,
                'dia'       => 5,
                'frecuencia' => FinCompromiso::FRECUENCIA_MENSUAL,
            ],
            [
                'nombre'    => 'Claude AI (Anthropic)',
                'tipo'      => FinCompromiso::TIPO_SUSCRIPCION,
                'bolsa'     => 'necesidad',
                'monto'     => 520.00, // ~$20 USD aprox en HNL
                'dia'       => 20,
                'moneda'    => 'USD',
                'frecuencia' => FinCompromiso::FRECUENCIA_MENSUAL,
            ],
            // DEUDAS
            [
                'nombre'    => 'Tarjeta de crédito BHD',
                'tipo'      => FinCompromiso::TIPO_CREDITO,
                'bolsa'     => 'deuda',
                'monto'     => 0.00, // cuota variable — se actualiza cada mes
                'dia'       => 25,
                'frecuencia' => FinCompromiso::FRECUENCIA_MENSUAL,
            ],
        ];

        foreach ($compromisosDef as $def) {
            $existe = $this->compromisoRepo->findOneBy(['tenantId' => $tenantId, 'nombre' => $def['nombre']]);
            if ($existe !== null) {
                $io->writeln('  <comment>Omitido (ya existe):</comment> ' . $def['nombre']);
                continue;
            }

            /** @var FinBolsa $bolsa */
            $bolsa = $bolsas[$def['bolsa']] ?? null;
            if ($bolsa === null) {
                $io->warning('Bolsa no encontrada para: ' . $def['nombre'] . ' — saltando.');
                continue;
            }

            $compromiso = new FinCompromiso();
            $compromiso->setTenantId($tenantId);
            $compromiso->setNombre($def['nombre']);
            $compromiso->setTipo($def['tipo']);
            $compromiso->setBolsa($bolsa);
            $compromiso->setMontoCuota($def['monto']);
            $compromiso->setMoneda($def['moneda'] ?? 'HNL');
            $compromiso->setDiaPago($def['dia']);
            $compromiso->setFrecuencia($def['frecuencia']);
            $compromiso->setFechaInicio(new \DateTimeImmutable('2026-10-01'));
            $this->em->persist($compromiso);
            $io->writeln('  <info>Creado:</info> ' . $def['nombre'] . ' — L ' . number_format($def['monto'], 2));
        }
        $this->em->flush();

        // ── 3. ACREEDORES (plan Dabasir) ───────────────────────────────────────
        $io->section('Acreedores');
        $acreedoresDef = [
            [
                'nombre'    => 'Tarjeta BHD',
                'tipo'      => FinAcreedor::TIPO_CREDITO,
                'saldo'     => 15000.00,
                'tasa'      => 0.36, // 36% anual aprox tarjetas HN
            ],
        ];

        foreach ($acreedoresDef as $def) {
            $existe = $this->acreedorRepo->findOneBy(['tenantId' => $tenantId, 'nombre' => $def['nombre']]);
            if ($existe !== null) {
                $io->writeln('  <comment>Omitido (ya existe):</comment> ' . $def['nombre']);
                continue;
            }
            $acreedor = new FinAcreedor();
            $acreedor->setTenantId($tenantId);
            $acreedor->setNombre($def['nombre']);
            $acreedor->setTipo($def['tipo']);
            $acreedor->setSaldoHnl($def['saldo']);
            $acreedor->setTasaAnual($def['tasa']);
            $this->em->persist($acreedor);
            $io->writeln('  <info>Creado:</info> ' . $def['nombre'] . ' — L ' . number_format($def['saldo'], 2));
        }
        $this->em->flush();

        $io->success('Siembra completada para tenant_id=' . $tenantId);

        return Command::SUCCESS;
    }
}
