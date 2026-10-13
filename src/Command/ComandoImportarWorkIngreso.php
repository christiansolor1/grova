<?php

declare(strict_types=1);

namespace App\Command;

use App\Module\Personal\FinancialPlanning\Entity\FinIngreso;
use App\Module\Personal\FinancialPlanning\Repository\FinIngresoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Importa manualmente una factura de Work como FinIngreso en Planificación Financiera.
 * Uso: grova:financiera:importar-work <invoice_id> <tenant_id> [--babilonia]
 */
#[AsCommand(
    name: 'grova:financiera:importar-work',
    description: 'Importa una work_invoice como FinIngreso en Planificación Financiera.',
)]
final class ComandoImportarWorkIngreso extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FinIngresoRepository $ingresoRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('invoice_id', InputArgument::REQUIRED, 'ID de la work_invoice')
            ->addArgument('tenant_id',  InputArgument::REQUIRED, 'ID del tenant')
            ->addOption('babilonia', null, null, 'Reservar 10% Babilonia automáticamente');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $invoiceId = (int) $input->getArgument('invoice_id');
        $tenantId  = (int) $input->getArgument('tenant_id');
        $babilonia = (bool) $input->getOption('babilonia');

        // Leer work_invoice directamente via DBAL (tabla en misma BD)
        $conn = $this->em->getConnection();
        $row  = $conn->fetchAssociative(
            'SELECT wi.*, wc.nombre as cliente_nombre
             FROM work_invoice wi
             LEFT JOIN work_client wc ON wc.id = wi.client_id
             WHERE wi.id = :id AND wi.tenant_id = :tid',
            ['id' => $invoiceId, 'tid' => $tenantId]
        );

        if ($row === false) {
            $io->error("No se encontró la factura #{$invoiceId} para tenant {$tenantId}.");
            return Command::FAILURE;
        }

        // Verificar que no esté ya importada
        $existente = $this->ingresoRepo->findOneBy([
            'tenantId' => $tenantId,
            'fuente'   => "Work — Factura #{$row['numero']} ({$row['anio']}/{$row['mes']})",
        ]);
        if ($existente !== null) {
            $io->warning("Esta factura ya fue importada (FinIngreso id={$existente->getId()}).");
            return Command::SUCCESS;
        }

        // Calcular montos
        $totalEur     = (float) $row['total'];
        $comisionHnl  = (float) ($row['comision_banco_hnl'] ?? 0);
        $tasaCambio   = (float) ($row['tasa_pago_eur_l'] ?? $row['tasa_emision_eur_l'] ?? 1);

        if ($tasaCambio <= 0) {
            $io->error('La factura no tiene tasa de cambio EUR→HNL. Actualízala en Work primero.');
            return Command::FAILURE;
        }

        $montoBrutoHnl = round($totalEur * $tasaCambio, 2);
        $netoHnl       = round($montoBrutoHnl - $comisionHnl, 2);
        $ahorro        = $babilonia ? round($netoHnl * 0.10, 2) : 0.0;

        // Fecha: usamos pagada_at o primer día del mes siguiente al mes de la factura
        $fechaPago = null;
        if (!empty($row['pagada_at'])) {
            try { $fechaPago = new \DateTimeImmutable($row['pagada_at']); } catch (\Exception) {}
        }
        if ($fechaPago === null) {
            $fechaPago = new \DateTimeImmutable(sprintf('%04d-%02d-01', $row['anio'], $row['mes']));
        }

        $io->section('Datos de la importación');
        $io->table(
            ['Campo', 'Valor'],
            [
                ['Factura',       "#{$row['numero']} — {$row['cliente_nombre']}"],
                ['Período',       "{$row['anio']}/{$row['mes']} ({$row['dias_trabajados']} noches)"],
                ['Total',         "€ {$totalEur}"],
                ['Tasa EUR→HNL',  $tasaCambio],
                ['Bruto HNL',     'L ' . number_format($montoBrutoHnl, 2)],
                ['Comisión banco','L ' . number_format($comisionHnl, 2)],
                ['Neto HNL',      'L ' . number_format($netoHnl, 2)],
                ['Babilonia 10%', $babilonia ? 'L ' . number_format($ahorro, 2) : 'No'],
                ['Fecha pago',    $fechaPago->format('Y-m-d')],
            ]
        );

        if (!$io->confirm('¿Importar este ingreso?', true)) {
            $io->warning('Cancelado.');
            return Command::SUCCESS;
        }

        $ingreso = new FinIngreso();
        $ingreso->setTenantId($tenantId);
        $ingreso->setFuente("Work — Factura #{$row['numero']} ({$row['anio']}/{$row['mes']})");
        $ingreso->setFecha($fechaPago);
        $ingreso->setMontoBruto($totalEur);
        $ingreso->setComisiones(0.0);
        $ingreso->setRetenciones(0.0);
        $ingreso->setMoneda('EUR');
        $ingreso->setTasaCambio($tasaCambio);
        // El neto HNL ya descuenta comisión banco como si fuera parte del cálculo de conversión
        $ingreso->setNetoHnl($netoHnl);
        $ingreso->setAhorroReservadoHnl($ahorro);
        $ingreso->setNota("Comisión banco: L " . number_format($comisionHnl, 2) . " · {$row['dias_trabajados']} noches trabajadas");

        $this->em->persist($ingreso);
        $this->em->flush();

        $io->success("Ingreso creado (id={$ingreso->getId()}). Neto: L " . number_format($netoHnl, 2));

        return Command::SUCCESS;
    }
}
