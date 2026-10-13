<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Wallet\Entity\WalletEntry;
use App\Module\Personal\Wallet\Entity\WalletGastoFijo;
use App\Module\Personal\Wallet\Entity\WalletGastoFijoPago;
use App\Module\Personal\Wallet\Repository\WalletCategoryRepository;
use App\Module\Personal\Wallet\Repository\WalletCuentaRepository;
use App\Module\Personal\Wallet\Repository\WalletGastoFijoRepository;
use App\Module\Personal\Wallet\Repository\WalletGastoFijoPagoRepository;
use App\Service\EncryptionService;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/wallet/gastos-fijos', name: 'grova_wallet_gasto_fijo_')]
final class WalletGastoFijoController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly EncryptionService $encryption,
        private readonly WalletGastoFijoRepository $gastoRepo,
        private readonly WalletGastoFijoPagoRepository $pagoRepo,
        private readonly WalletCuentaRepository $cuentaRepo,
        private readonly WalletCategoryRepository $categoryRepo,
    ) {
    }

    /** Página de detalle/amortización de un compromiso */
    #[Route('/{id}', name: 'detalle', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detalle(int $id): Response
    {
        $gasto = $this->gastoRepo->find($id);
        if ($gasto === null) {
            throw $this->createNotFoundException('Compromiso no encontrado.');
        }

        $pagosRaw  = $this->pagoRepo->findAllByGasto($gasto);
        $pagosMapa = $this->buildPagosMapa($pagosRaw);
        $schedule  = $this->buildSchedule($gasto, $pagosMapa);

        $hoy           = new \DateTimeImmutable();
        $totalPagado   = array_sum(array_map(fn($p) => $p->getMontoFloat(), $pagosRaw));
        $cuotasPagadas = count(array_unique(array_map(fn($p) => $p->getAnio() . '_' . $p->getMes(), $pagosRaw)));
        $cuotaActual   = $gasto->getCuotaNumero((int) $hoy->format('Y'), (int) $hoy->format('n'));

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('workspace/pages/wallet/gastoFijoDetalle.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'wallet',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'gasto'                   => $gasto,
            'schedule'                => $schedule,
            'pagos_raw'               => $pagosRaw,
            'total_pagado'            => $totalPagado,
            'cuotas_pagadas'          => $cuotasPagadas,
            'cuota_actual'            => $cuotaActual,
        ]);
    }

    /** Datos del schedule para actualización AJAX sin recarga de página */
    #[Route('/{id}/schedule-data', name: 'schedule_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function scheduleData(int $id): JsonResponse
    {
        $gasto = $this->gastoRepo->find($id);
        if ($gasto === null) {
            return new JsonResponse(['success' => false], 404);
        }

        $pagosRaw  = $this->pagoRepo->findAllByGasto($gasto);
        $pagosMapa = $this->buildPagosMapa($pagosRaw);
        $schedule  = $this->buildSchedule($gasto, $pagosMapa);

        $scheduleData = array_map(function (array $item): array {
            $pago = $item['pago'];
            return [
                'anio'           => $item['anio'],
                'mes'            => $item['mes'],
                'mes_label'      => $item['mes_label'],
                'monto'          => $item['monto'] ?? 0.0,
                'monto_pagado'   => $item['monto_pagado'],
                'estado'         => $item['estado'],
                'dia_pago_real'  => $item['dia_pago_real'],
                'dia_corte_real' => $item['dia_corte_real'],
                'es_prima'       => $item['es_prima'],
                'cuota'          => $item['cuota'] ?? null,
                'pago'           => $pago ? [
                    'id'         => $pago->getId(),
                    'montoFloat' => $pago->getMontoFloat(),
                    'fecha'      => $pago->getFecha()->format('d/m/Y'),
                    'esExtra'    => $pago->isEsExtra(),
                ] : null,
                'token_revertir' => $pago
                    ? $this->generateToken('wallet_gasto_fijo_revertir_' . $pago->getId())
                    : null,
            ];
        }, $schedule);

        $hoy           = new \DateTimeImmutable();
        $totalPagado   = array_sum(array_map(fn($p) => $p->getMontoFloat(), $pagosRaw));
        $cuotasPagadas = count(array_unique(array_map(fn($p) => $p->getAnio() . '_' . $p->getMes(), $pagosRaw)));
        $cuotaActual   = $gasto->getCuotaNumero((int) $hoy->format('Y'), (int) $hoy->format('n'));

        return new JsonResponse([
            'schedule'       => $scheduleData,
            'total_pagado'   => $totalPagado,
            'cuotas_pagadas' => $cuotasPagadas,
            'cuota_actual'   => $cuotaActual,
            'csrf_pagar'     => $this->generateToken('wallet_gasto_fijo_pagar_' . $gasto->getId()),
        ]);
    }

    /** Lista completa con estado del mes actual */
    #[Route('/data', name: 'data', methods: ['GET'])]
    public function data(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $now      = new \DateTimeImmutable();
        $anio     = (int) ($request->query->get('anio', (string) $now->format('Y')));
        $mes      = (int) ($request->query->get('mes', (string) $now->format('n')));

        $gastos = $this->gastoRepo->findTodos($tenantId);
        $pagos  = $this->pagoRepo->findPagosMes($gastos, $anio, $mes);

        $rows = [];
        $totalMes     = 0.0;
        $totalPagado  = 0.0;

        foreach ($gastos as $g) {
            $pagosDelGasto = $pagos[$g->getId()] ?? [];
            $totalGastado  = array_sum(array_map(fn($p) => $p->getMontoFloat(), $pagosDelGasto));
            $estado        = $g->getEstado($anio, $mes, empty($pagosDelGasto) ? null : $pagosDelGasto[0]);

            $pagosData = array_map(fn($p) => [
                'id'            => $p->getId(),
                'fecha'         => $p->getFecha()->format('d/m/Y'),
                'monto'         => $p->getMontoFloat(),
                'montoFmt'      => 'L ' . number_format($p->getMontoFloat(), 2, '.', ','),
                'cantidad'      => $p->getCantidadFloat() ?: null,
                'unidad'        => $p->getUnidad(),
                'precioUnit'    => $p->getPrecioUnitarioFloat() ?: null,
                'esExtra'       => $p->isEsExtra(),
                'tokenRevertir' => $this->generateToken('wallet_gasto_fijo_revertir_' . $p->getId()),
            ], $pagosDelGasto);

            if ($g->isActivo()) {
                $totalMes    += $g->getMontoFloat();
                $totalPagado += $totalGastado;
            }

            $montoMes       = $g->getMontoParaMes($anio, $mes);
            $cuotaNum       = $g->getCuotaNumero($anio, $mes);
            $mesesRestantes = $g->getMesesRestantes();
            $fechaFin       = $g->getFechaFinPlan();

            $rows[] = [
                'id'                     => $g->getId(),
                'nombre'                 => $g->getNombre(),
                'tipo'                   => $g->getTipo(),
                'tipoLabel'              => $g->getTipoLabel(),
                'monto'                  => $montoMes,
                'montoFmt'               => 'L ' . number_format($montoMes, 2, '.', ','),
                'montoBase'              => $g->getMontoFloat(),
                'diaPago'                => $g->getDiaPago(),
                'diaCorte'               => $g->getDiaCorte(),
                'frecuencia'             => $g->getFrecuencia(),
                'unidad'                 => $g->getUnidad(),
                'presentacion'           => $g->getPresentacion(),
                'precioUnitario'         => $g->getPrecioUnitarioFloat() ?: null,
                'cantidadBase'           => $g->getCantidadBaseFloat() ?: null,
                // Financiamiento
                'fechaInicioPlan'        => $g->getFechaInicioPlan()?->format('Y-m-d'),
                'mesesFinanciamiento'    => $g->getMesesFinanciamiento(),
                'montoPrima'             => $g->getMontoPrimaFloat() ?: null,
                'cuotasEspeciales'       => $g->getCuotasEspecialesArray(),
                'montoPostFinanciamiento'=> $g->getMontoPostFinanciamientoFloat() ?: null,
                'cuotaActual'            => $cuotaNum,
                'mesesRestantes'         => $mesesRestantes,
                'fechaFinPlan'           => $fechaFin?->format('Y-m'),
                'costoTotalFinanciado'   => $g->getCostoTotalFinanciado() ?: null,
                // Relaciones
                'cuenta'                 => $g->getCuenta() ? $g->getCuenta()->getNombre() : null,
                'cuentaId'               => $g->getCuenta() ? $g->getCuenta()->getId() : null,
                'categoryId'             => $g->getCategory() ? $g->getCategory()->getId() : null,
                'nota'                   => $g->getNota(),
                'urlPago'                => $g->getUrlPago(),
                'numeroCliente'          => $g->getNumeroCliente(),
                'usuarioAcceso'          => $g->getUsuarioAcceso(),
                'claveAcceso'            => $g->getClaveAcceso() ? $this->encryption->decrypt($g->getClaveAcceso()) : null,
                'activo'                 => $g->isActivo(),
                'estado'                 => $estado,
                'totalGastado'           => $totalGastado,
                'pagos'                  => $pagosData,
                'tokenPagar'             => $this->generateToken('wallet_gasto_fijo_pagar_' . $g->getId()),
                'tokenEdit'              => $this->generateToken('wallet_gasto_fijo_edit_' . $g->getId()),
                'tokenDelete'            => $this->generateToken('wallet_gasto_fijo_delete_' . $g->getId()),
            ];
        }

        $totalPendiente = max(0.0, $totalMes - $totalPagado);

        return new JsonResponse([
            'gastos'         => $rows,
            'totalMes'       => $totalMes,
            'totalPagado'    => $totalPagado,
            'totalPendiente' => $totalPendiente,
            'anio'           => $anio,
            'mes'            => $mes,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_gasto_fijo_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $nombre   = trim((string) $request->request->get('nombre', ''));
        $monto    = (float) $request->request->get('monto', '0');
        $diaPago  = max(1, min(31, (int) $request->request->get('dia_pago', '1')));

        if ($nombre === '' || $monto <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Nombre y monto son obligatorios.'], 422);
        }

        $gasto = new WalletGastoFijo();
        $gasto->setTenantId($tenantId);
        $gasto->setNombre($nombre);
        $gasto->setMonto(number_format($monto, 2, '.', ''));
        $gasto->setDiaPago($diaPago);
        $gasto->setFrecuencia((string) $request->request->get('frecuencia', 'mensual'));
        $gasto->setUnidad(trim((string) $request->request->get('unidad', '')) ?: null);
        $gasto->setPresentacion(trim((string) $request->request->get('presentacion', '')) ?: null);
        $precioUnitNew   = $request->request->get('precio_unitario', '');
        $cantidadBaseNew = $request->request->get('cantidad_base', '');
        $gasto->setPrecioUnitario($precioUnitNew !== '' ? number_format((float) $precioUnitNew, 2, '.', '') : null);
        $gasto->setCantidadBase($cantidadBaseNew !== '' ? number_format((float) $cantidadBaseNew, 2, '.', '') : null);
        $gasto->setNota(trim((string) $request->request->get('nota', '')) ?: null);

        $this->asignarFinanciamiento($gasto, $request);
        $this->asignarAcceso($gasto, $request);
        $this->asignarCuentaYCategoria($gasto, $request, $tenantId);

        $em->persist($gasto);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Gasto fijo creado.', 'id' => $gasto->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_gasto_fijo_edit_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $gasto = $this->gastoRepo->find($id);
        if ($gasto === null) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrado.'], 404);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $nombre   = trim((string) $request->request->get('nombre', ''));
        $monto    = (float) $request->request->get('monto', '0');
        $diaPago  = max(1, min(31, (int) $request->request->get('dia_pago', '1')));

        if ($nombre === '' || $monto <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Nombre y monto son obligatorios.'], 422);
        }

        $gasto->setNombre($nombre);
        $gasto->setMonto(number_format($monto, 2, '.', ''));
        $gasto->setDiaPago($diaPago);
        $gasto->setFrecuencia((string) $request->request->get('frecuencia', 'mensual'));
        $gasto->setUnidad(trim((string) $request->request->get('unidad', '')) ?: null);
        $gasto->setPresentacion(trim((string) $request->request->get('presentacion', '')) ?: null);
        $precioUnitEdit  = $request->request->get('precio_unitario', '');
        $cantidadBaseEdit = $request->request->get('cantidad_base', '');
        $gasto->setPrecioUnitario($precioUnitEdit !== '' ? number_format((float) $precioUnitEdit, 2, '.', '') : null);
        $gasto->setCantidadBase($cantidadBaseEdit !== '' ? number_format((float) $cantidadBaseEdit, 2, '.', '') : null);
        $gasto->setNota(trim((string) $request->request->get('nota', '')) ?: null);
        $gasto->setActivo((bool) $request->request->get('activo', '1'));
        $this->asignarFinanciamiento($gasto, $request);
        $this->asignarAcceso($gasto, $request);
        $this->asignarCuentaYCategoria($gasto, $request, $tenantId);

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Gasto fijo actualizado.']);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_gasto_fijo_delete_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $gasto = $this->gastoRepo->find($id);
        if ($gasto !== null) {
            $em->remove($gasto);
            $em->flush();
        }

        return new JsonResponse(['success' => true, 'message' => 'Gasto fijo eliminado.']);
    }

    /** Marcar como pagado en el mes actual — crea WalletEntry automáticamente */
    #[Route('/{id}/pagar', name: 'pagar', methods: ['POST'])]
    public function pagar(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_gasto_fijo_pagar_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $gasto = $this->gastoRepo->find($id);
        if ($gasto === null) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrado.'], 404);
        }

        $now  = new \DateTimeImmutable();
        $anio = (int) ($request->request->get('anio', (string) $now->format('Y')));
        $mes  = (int) ($request->request->get('mes', (string) $now->format('n')));

        $tenantId        = $this->tenantContext->getTenant()->getId();
        $fechaStr        = (string) $request->request->get('fecha', $now->format('Y-m-d'));
        $fechaPago       = new \DateTimeImmutable($fechaStr);
        $anio            = (int) $fechaPago->format('Y');
        $mes             = (int) $fechaPago->format('n');

        // Cantidad y precio unitario
        $cantidadRaw     = $request->request->get('cantidad', '');
        $precioUnitRaw   = $request->request->get('precio_unitario', '');
        $cantidad        = $cantidadRaw !== '' ? (float) $cantidadRaw : null;
        $precioUnit      = $precioUnitRaw !== '' ? (float) $precioUnitRaw : null;

        // Calcular monto
        if ($cantidad !== null && $precioUnit !== null && $cantidad > 0 && $precioUnit > 0) {
            $montoReal = round($cantidad * $precioUnit, 2);
        } else {
            $montoSugerido = $gasto->getMontoParaMes($anio, $mes);
            $montoReal = (float) $request->request->get('monto', (string) $montoSugerido);
            $montoReal = $montoReal > 0 ? $montoReal : $montoSugerido;
        }

        $esExtra         = $request->request->get('es_extra') === '1';
        $actualizarMonto = $request->request->get('actualizar_monto') === '1';

        // Actualizar precio unitario base si cambió
        if ($actualizarMonto) {
            if ($precioUnit !== null && $precioUnit > 0) {
                $gasto->setPrecioUnitario(number_format($precioUnit, 2, '.', ''));
                // Recalcular monto base si tiene cantidad base
                if ($gasto->getCantidadBaseFloat() > 0) {
                    $gasto->setMonto(number_format($gasto->getCantidadBaseFloat() * $precioUnit, 2, '.', ''));
                }
            } elseif (abs($montoReal - $gasto->getMontoFloat()) > 0.001) {
                $gasto->setMonto(number_format($montoReal, 2, '.', ''));
            }
        }

        // Crear WalletEntry
        $desc = $gasto->getNombre();
        if ($cantidad !== null && $gasto->getUnidad()) {
            $desc .= ' (' . number_format($cantidad, 0) . ' ' . $gasto->getUnidad() . ')';
        }

        $entry = new WalletEntry();
        $entry->setTenantId($tenantId);
        $entry->setTipo('gasto');
        $entry->setMonto(number_format($montoReal, 2, '.', ''));
        $entry->setDescripcion($desc);
        $entry->setFecha($fechaPago);
        if ($gasto->getCuenta() !== null) {
            $entry->setCuenta($gasto->getCuenta());
        }
        if ($gasto->getCategory() !== null) {
            $entry->setCategory($gasto->getCategory());
        }
        $em->persist($entry);
        $em->flush();

        // Crear registro de pago
        $pago = new WalletGastoFijoPago();
        $pago->setGastoFijo($gasto);
        $pago->setAnio($anio);
        $pago->setMes($mes);
        $pago->setFecha($fechaPago);
        $pago->setMonto(number_format($montoReal, 2, '.', ''));
        $pago->setCantidad($cantidad !== null ? number_format($cantidad, 2, '.', '') : null);
        $pago->setUnidad($gasto->getUnidad());
        $pago->setPrecioUnitario($precioUnit !== null ? number_format($precioUnit, 2, '.', '') : null);
        $pago->setEsExtra($esExtra);
        $pago->setWalletEntryId($entry->getId());
        $em->persist($pago);
        $em->flush();

        return new JsonResponse([
            'success'  => true,
            'message'  => $gasto->getNombre() . ' — L ' . number_format($montoReal, 2) . ' registrado.',
            'pagoId'   => $pago->getId(),
            'fecha'    => $pago->getFecha()->format('d/m/Y'),
        ]);
    }

    /** Revertir pago del mes */
    #[Route('/pago/{pagoId}/revertir', name: 'revertir', methods: ['POST'])]
    public function revertir(int $pagoId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_gasto_fijo_revertir_' . $pagoId, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $pago = $this->pagoRepo->find($pagoId);
        if ($pago === null) {
            return new JsonResponse(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        // Eliminar WalletEntry asociado si existe
        if ($pago->getWalletEntryId() !== null) {
            $entryRepo = $em->getRepository(WalletEntry::class);
            $entry = $entryRepo->find($pago->getWalletEntryId());
            if ($entry !== null) {
                $em->remove($entry);
            }
        }

        $em->remove($pago);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Pago revertido.']);
    }

    /** @param WalletGastoFijoPago[] $pagosRaw */
    private function buildPagosMapa(array $pagosRaw): array
    {
        $mapa = [];
        foreach ($pagosRaw as $p) {
            $key = $p->getAnio() . '_' . $p->getMes();
            if (!isset($mapa[$key])) {
                $mapa[$key] = $p;
            }
        }
        return $mapa;
    }

    private function buildSchedule(WalletGastoFijo $gasto, array $pagosMapa): array
    {
        $hoy   = new \DateTimeImmutable();
        $hoyA  = (int) $hoy->format('Y');
        $hoyM  = (int) $hoy->format('n');
        $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $makeItem = function (int $anioNum, int $mesNum, ?WalletGastoFijoPago $pago, string $estado) use ($gasto, $meses): array {
            return [
                'anio'           => $anioNum,
                'mes'            => $mesNum,
                'mes_label'      => $meses[$mesNum] . ' ' . $anioNum,
                'pago'           => $pago,
                'monto_pagado'   => $pago ? $pago->getMontoFloat() : 0.0,
                'estado'         => $estado,
                'dia_pago_real'  => $gasto->getDiaPagoEfectivo($anioNum, $mesNum),
                'dia_corte_real' => $gasto->getDiaCorteEfectivo($anioNum, $mesNum),
                'es_prima'       => false,
            ];
        };

        $schedule = [];

        if (in_array($gasto->getTipo(), ['financiado', 'prestamo'], true)
            && $gasto->getFechaInicioPlan() !== null
            && $gasto->getMesesFinanciamiento() !== null
        ) {
            $inicioA = (int) $gasto->getFechaInicioPlan()->format('Y');
            $inicioM = (int) $gasto->getFechaInicioPlan()->format('n');

            if ($gasto->getMontoPrimaFloat() > 0) {
                $schedule[] = [
                    'cuota'          => 0,
                    'anio'           => $inicioA,
                    'mes'            => $inicioM,
                    'mes_label'      => $meses[$inicioM] . ' ' . $inicioA,
                    'monto'          => $gasto->getMontoPrimaFloat(),
                    'pago'           => null,
                    'monto_pagado'   => 0.0,
                    'estado'         => 'info',
                    'es_prima'       => true,
                    'dia_pago_real'  => $gasto->getDiaPagoEfectivo($inicioA, $inicioM),
                    'dia_corte_real' => $gasto->getDiaCorteEfectivo($inicioA, $inicioM),
                ];
            }

            for ($i = 0; $i < $gasto->getMesesFinanciamiento(); $i++) {
                $mesNum  = (($inicioM - 1 + $i) % 12) + 1;
                $anioNum = $inicioA + (int) floor(($inicioM - 1 + $i) / 12);
                $key     = $anioNum . '_' . $mesNum;
                $pago    = $pagosMapa[$key] ?? null;
                $item    = $makeItem($anioNum, $mesNum, $pago, $gasto->getEstado($anioNum, $mesNum, $pago));
                $item['cuota'] = $i + 1;
                $item['monto'] = $gasto->getMontoParaMes($anioNum, $mesNum);
                $schedule[] = $item;
            }

            if ($gasto->getMontoPostFinanciamientoFloat() > 0 && $gasto->getFechaFinPlan() !== null) {
                $finA = (int) $gasto->getFechaFinPlan()->format('Y');
                $finM = (int) $gasto->getFechaFinPlan()->format('n');
                for ($j = 1; $j <= 3; $j++) {
                    $mesNum  = (($finM - 1 + $j) % 12) + 1;
                    $anioNum = $finA + (int) floor(($finM - 1 + $j) / 12);
                    $key     = $anioNum . '_' . $mesNum;
                    $pago    = $pagosMapa[$key] ?? null;
                    $item    = $makeItem($anioNum, $mesNum, $pago, 'post');
                    $item['cuota'] = null;
                    $item['monto'] = $gasto->getMontoPostFinanciamientoFloat();
                    $schedule[] = $item;
                }
            }
        } else {
            for ($i = -12; $i <= 3; $i++) {
                $offset  = $hoyM - 1 + $i;
                $mesNum  = (($offset % 12) + 12) % 12 + 1;
                $anioNum = $hoyA + (int) floor($offset / 12);
                $key     = $anioNum . '_' . $mesNum;
                $pago    = $pagosMapa[$key] ?? null;
                $item    = $makeItem($anioNum, $mesNum, $pago, $gasto->getEstado($anioNum, $mesNum, $pago));
                $item['cuota'] = null;
                $item['monto'] = $gasto->getMontoParaMes($anioNum, $mesNum);
                $schedule[] = $item;
            }
        }

        return $schedule;
    }

    private function asignarFinanciamiento(WalletGastoFijo $gasto, Request $request): void
    {
        $tipo = (string) $request->request->get('tipo', 'generico');
        $gasto->setTipo(in_array($tipo, WalletGastoFijo::TIPOS, true) ? $tipo : 'generico');

        $diaCorteRaw = $request->request->get('dia_corte', '');
        $gasto->setDiaCorte($diaCorteRaw !== '' ? max(1, min(31, (int) $diaCorteRaw)) : null);

        if (in_array($tipo, ['financiado', 'prestamo'], true)) {
            $fechaStr = trim((string) $request->request->get('fecha_inicio_plan', ''));
            $gasto->setFechaInicioPlan($fechaStr !== '' ? new \DateTimeImmutable($fechaStr) : null);

            $meses = $request->request->get('meses_financiamiento', '');
            $gasto->setMesesFinanciamiento($meses !== '' ? max(1, (int) $meses) : null);

            $prima = $request->request->get('monto_prima', '');
            $gasto->setMontoPrima($prima !== '' && (float) $prima > 0 ? number_format((float) $prima, 2, '.', '') : null);

            // Cuotas especiales vienen como JSON string desde el frontend
            $cuotasJson = trim((string) $request->request->get('cuotas_especiales', ''));
            if ($cuotasJson !== '' && $cuotasJson !== '[]') {
                $decoded = json_decode($cuotasJson, true);
                $gasto->setCuotasEspeciales(is_array($decoded) && count($decoded) > 0 ? $cuotasJson : null);
            } else {
                $gasto->setCuotasEspeciales(null);
            }

            $postFin = $request->request->get('monto_post_financiamiento', '');
            $gasto->setMontoPostFinanciamiento($postFin !== '' && (float) $postFin > 0 ? number_format((float) $postFin, 2, '.', '') : null);
        } else {
            // Limpiar campos de financiamiento si no aplica
            $gasto->setFechaInicioPlan(null);
            $gasto->setMesesFinanciamiento(null);
            $gasto->setMontoPrima(null);
            $gasto->setCuotasEspeciales(null);
            $gasto->setMontoPostFinanciamiento(null);
        }
    }

    private function asignarAcceso(WalletGastoFijo $gasto, Request $request): void
    {
        $gasto->setUrlPago(trim((string) $request->request->get('url_pago', '')) ?: null);
        $gasto->setNumeroCliente(trim((string) $request->request->get('numero_cliente', '')) ?: null);
        $gasto->setUsuarioAcceso(trim((string) $request->request->get('usuario_acceso', '')) ?: null);

        $clave = trim((string) $request->request->get('clave_acceso', ''));
        if ($clave !== '') {
            $gasto->setClaveAcceso($this->encryption->encrypt($clave));
        } elseif ($request->request->get('clave_acceso') !== null) {
            // Campo enviado vacío → borrar la clave guardada
            $gasto->setClaveAcceso(null);
        }
        // Si el campo no se envió en absoluto (null), no tocar el valor existente
    }

    private function asignarCuentaYCategoria(WalletGastoFijo $gasto, Request $request, int $tenantId): void
    {
        $cuentaId = $request->request->get('cuenta_id', '');
        if ($cuentaId !== '' && $cuentaId !== null) {
            $cuenta = $this->cuentaRepo->find((int) $cuentaId);
            $gasto->setCuenta($cuenta !== null && $cuenta->getTenantId() === $tenantId ? $cuenta : null);
        } else {
            $gasto->setCuenta(null);
        }

        $categoryId = $request->request->get('category_id', '');
        if ($categoryId !== '' && $categoryId !== null) {
            $cat = $this->categoryRepo->find((int) $categoryId);
            $gasto->setCategory($cat !== null ? $cat : null);
        } else {
            $gasto->setCategory(null);
        }
    }

    private function generateToken(string $id): string
    {
        return $this->container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }
}
