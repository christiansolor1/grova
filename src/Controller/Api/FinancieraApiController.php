<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Module\Personal\FinancialPlanning\Entity\FinDocumento;
use App\Module\Personal\FinancialPlanning\Entity\FinGasto;
use App\Module\Personal\FinancialPlanning\Entity\FinIngreso;
use App\Module\Personal\FinancialPlanning\Entity\FinPago;
use App\Module\Personal\FinancialPlanning\Repository\FinAcreedorRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinBolsaRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinCompromisoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinCuotaRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinDocumentoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinGastoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinIngresoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinMetaRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinPagoRepository;
use App\Module\Personal\FinancialPlanning\Service\FinDocumentoStorage;
use App\Service\TenantContext;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/financiera', name: 'api_financiera_')]
final class FinancieraApiController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly FinIngresoRepository $ingresoRepo,
        private readonly FinGastoRepository $gastoRepo,
        private readonly FinBolsaRepository $bolsaRepo,
        private readonly FinCuotaRepository $cuotaRepo,
        private readonly FinCompromisoRepository $compromisoRepo,
        private readonly FinAcreedorRepository $acreedorRepo,
        private readonly FinMetaRepository $metaRepo,
        private readonly FinPagoRepository $pagoRepo,
        private readonly FinDocumentoRepository $documentoRepo,
        private readonly FinDocumentoStorage $documentoStorage,
    ) {}

    // ── Resumen global ───────────────────────────────────────────────────────

    #[Route('/resumen', name: 'resumen', methods: ['GET'])]
    public function resumen(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $globalIngresos = $this->ingresoRepo->sumNetoHnlByTenant($tenantId);
        $globalGastos   = $this->gastoRepo->sumMontoHnlByTenant($tenantId);
        $globalAhorro   = $this->ingresoRepo->sumAhorroByTenant($tenantId);
        $globalDeuda    = $this->acreedorRepo->sumSaldoActivosByTenant($tenantId);
        $saldo          = round($globalIngresos - $globalGastos, 2);

        $compromisos  = $this->compromisoRepo->findActivosByTenant($tenantId);
        $cuotaMensual = array_sum(array_map(
            fn($c) => $c->getMoneda() === 'HNL' ? $c->getMontoCuota() : 0.0,
            $compromisos
        ));

        $proximas = $this->cuotaRepo->findProximasByTenant($tenantId, 60);

        $cuotasPendientes = array_filter($proximas, fn($c) => $c->getEstado() !== 'pagado');
        $totalPendiente   = array_sum(array_map(fn($c) => $c->getSaldoPendiente(), $cuotasPendientes));

        return $this->json([
            'saldoDisponible'  => $saldo,
            'globalIngresos'   => $globalIngresos,
            'globalGastos'     => $globalGastos,
            'babiloniaAhorro'  => $globalAhorro,
            'deudaActiva'      => $globalDeuda,
            'cuotaMensual'     => round($cuotaMensual, 2),
            'cuotasPendientes' => count($cuotasPendientes),
            'totalPendiente'   => round($totalPendiente, 2),
        ]);
    }

    // ── Cuotas próximas ──────────────────────────────────────────────────────

    #[Route('/cuotas/proximas', name: 'cuotas_proximas', methods: ['GET'])]
    public function cuotasProximas(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $dias    = (int) ($request->query->get('dias') ?? 60);
        $proximas = $this->cuotaRepo->findProximasByTenant($tenantId, $dias);

        return $this->json(array_map(fn($c) => [
            'id'               => $c->getId(),
            'compromiso'       => $c->getCompromiso()->getNombre(),
            'numero'           => $c->getNumero(),
            'fechaVencimiento' => $c->getFechaVencimiento()->format('Y-m-d'),
            'montoTotal'       => $c->getMontoTotal(),
            'montoPagado'      => $c->getMontoPagado(),
            'saldoPendiente'   => $c->getSaldoPendiente(),
            'estado'           => $c->getEstado(),
        ], $proximas));
    }

    // ── Movimientos del mes ──────────────────────────────────────────────────

    #[Route('/movimientos', name: 'movimientos', methods: ['GET'])]
    public function movimientos(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $anio = (int) ($request->query->get('anio') ?? date('Y'));
        $mes  = (int) ($request->query->get('mes')  ?? date('n'));

        $ingresos = $this->ingresoRepo->findByTenantMes($tenantId, $anio, $mes);
        $gastos   = $this->gastoRepo->findByTenantMes($tenantId, $anio, $mes);

        $totalIngresos = array_sum(array_map(fn($i) => $i->getNetoHnl(), $ingresos));
        $totalGastos   = array_sum(array_map(fn($g) => $g->getMontoHnl(), $gastos));

        return $this->json([
            'anio'         => $anio,
            'mes'          => $mes,
            'totalIngresos'=> round($totalIngresos, 2),
            'totalGastos'  => round($totalGastos, 2),
            'flujoNeto'    => round($totalIngresos - $totalGastos, 2),
            'ingresos'     => array_map(fn($i) => [
                'id'                 => $i->getId(),
                'fuente'             => $i->getFuente(),
                'fecha'              => $i->getFecha()->format('Y-m-d'),
                'montoBruto'         => $i->getMontoBruto(),
                'moneda'             => $i->getMoneda(),
                'tasaCambio'         => $i->getTasaCambio(),
                'netoHnl'            => $i->getNetoHnl(),
                'ahorroReservadoHnl' => $i->getAhorroReservadoHnl(),
                'nota'               => $i->getNota(),
            ], $ingresos),
            'gastos'       => array_map(fn($g) => [
                'id'          => $g->getId(),
                'descripcion' => $g->getDescripcion(),
                'fecha'       => $g->getFecha()->format('Y-m-d'),
                'monto'       => $g->getMonto(),
                'moneda'      => $g->getMoneda(),
                'tasaCambio'  => $g->getTasaCambio(),
                'montoHnl'    => $g->getMontoHnl(),
                'naturaleza'  => $g->getNaturaleza(),
                'bolsa'       => $g->getBolsa()->getNombre(),
                'bolsaColor'  => $g->getBolsa()->getColor(),
                'metodo'      => $g->getMetodo(),
                'nota'        => $g->getNota(),
            ], $gastos),
        ]);
    }

    // ── Bolsas ───────────────────────────────────────────────────────────────

    #[Route('/bolsas', name: 'bolsas', methods: ['GET'])]
    public function bolsas(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $bolsas = $this->bolsaRepo->findActivasByTenant($tenantId);

        return $this->json(array_map(fn($b) => [
            'id'                 => $b->getId(),
            'nombre'             => $b->getNombre(),
            'tipo'               => $b->getTipo(),
            'color'              => $b->getColor(),
            'porcentajeSugerido' => $b->getPorcentajeSugerido(),
        ], $bolsas));
    }

    // ── Metas ────────────────────────────────────────────────────────────────

    #[Route('/metas', name: 'metas', methods: ['GET'])]
    public function metas(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $metas = $this->metaRepo->findByTenant($tenantId, soloActivas: true);

        return $this->json(array_map(fn($m) => [
            'id'                    => $m->getId(),
            'nombre'                => $m->getNombre(),
            'tipo'                  => $m->getTipo(),
            'montoObjetivo'         => $m->getMontoObjetivo(),
            'montoAcumulado'        => $m->getMontoAcumulado(),
            'aporteMensualSugerido' => $m->getAporteMensualSugerido(),
            'porcentajeAvance'      => $m->getPorcentajeAvance(),
            'faltante'              => $m->getFaltante(),
            'fechaObjetivo'         => $m->getFechaObjetivo()?->format('Y-m-d'),
        ], $metas));
    }

    // ── Crear ingreso ────────────────────────────────────────────────────────

    #[Route('/ingreso', name: 'ingreso_crear', methods: ['POST'])]
    public function crearIngreso(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $fechaStr = $data['fecha'] ?? date('Y-m-d');
        try { $fecha = new \DateTimeImmutable($fechaStr); } catch (\Exception) { $fecha = new \DateTimeImmutable(); }

        $montoBruto  = (float) ($data['montoBruto'] ?? 0);
        $comisiones  = (float) ($data['comisiones'] ?? 0);
        $retenciones = (float) ($data['retenciones'] ?? 0);
        $tasaCambio  = max(0.00001, (float) ($data['tasaCambio'] ?? 1.0));
        $moneda      = (string) ($data['moneda'] ?? 'HNL');
        $babilonia   = (bool) ($data['babilonia'] ?? false);

        $netoOriginal = $montoBruto - $comisiones - $retenciones;
        $netoHnl      = round($netoOriginal * $tasaCambio, 2);
        $ahorro       = $babilonia ? round($netoHnl * 0.10, 2) : 0.0;

        $ingreso = new FinIngreso();
        $ingreso->setTenantId($tenantId);
        $ingreso->setFuente((string) ($data['fuente'] ?? ''));
        $ingreso->setFecha($fecha);
        $ingreso->setMontoBruto($montoBruto);
        $ingreso->setComisiones($comisiones);
        $ingreso->setRetenciones($retenciones);
        $ingreso->setMoneda($moneda);
        $ingreso->setTasaCambio($tasaCambio);
        $ingreso->setNetoHnl($netoHnl);
        $ingreso->setAhorroReservadoHnl($ahorro);
        $ingreso->setNota((string) ($data['nota'] ?? '') ?: null);

        $em->persist($ingreso);
        $em->flush();

        return $this->json([
            'id'                 => $ingreso->getId(),
            'netoHnl'            => $netoHnl,
            'ahorroReservadoHnl' => $ahorro,
        ], 201);
    }

    // ── Compromisos ──────────────────────────────────────────────────────────

    #[Route('/compromisos', name: 'compromisos', methods: ['GET'])]
    public function compromisos(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $lista          = $this->compromisoRepo->findActivosByTenant($tenantId);
        $resumenCuotas  = $this->cuotaRepo->resumenPorCompromiso($tenantId);
        $pagosSinCuota  = $this->pagoRepo->pagosSinCuotaPorCompromiso($tenantId);

        return $this->json(array_map(function ($c) use ($resumenCuotas, $pagosSinCuota) {
            $id             = $c->getId();
            $totalPlan      = $c->getTotalPlan();
            $pagadoCuotas   = $resumenCuotas[$id]['pagado'] ?? 0.0;
            $extra          = $pagosSinCuota[$id] ?? 0.0;
            $pagadoTotal    = $pagadoCuotas + $extra;
            $pendiente      = max(0.0, $totalPlan - $pagadoCuotas);
            $pct            = $totalPlan > 0 ? round(($pagadoCuotas / $totalPlan) * 100, 1) : 0.0;

            return [
                'id'              => $id,
                'nombre'          => $c->getNombre(),
                'tipo'            => $c->getTipo(),
                'montoCuota'      => $c->getMontoCuota(),
                'frecuencia'      => $c->getFrecuencia(),
                'diaPago'         => $c->getDiaPago(),
                'moneda'          => $c->getMoneda(),
                'totalPlan'       => round($totalPlan, 2),
                'pagadoCuotas'    => round($pagadoCuotas, 2),
                'pagosSinCuota'   => round($extra, 2),
                'pagadoTotal'     => round($pagadoTotal, 2),
                'pendiente'       => round($pendiente, 2),
                'pctCompletado'   => $pct,
                'fechaInicio'     => $c->getFechaInicio()->format('Y-m-d'),
                'fechaFin'        => $c->getFechaFin()?->format('Y-m-d'),
                'descripcion'     => $c->getDescripcion(),
            ];
        }, $lista));
    }

    // ── Plan de pago ─────────────────────────────────────────────────────────

    #[Route('/compromiso/{id}/plan', name: 'compromiso_plan', methods: ['GET'])]
    public function planPago(int $id): JsonResponse
    {
        $tenantId   = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $compromiso = $this->compromisoRepo->find($id);
        if ($compromiso === null || $compromiso->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $cuotasDb = $this->cuotaRepo->findByCompromiso($id);
        $cuotasPorFecha = [];
        foreach ($cuotasDb as $cq) {
            $cuotasPorFecha[$cq->getFechaVencimiento()->format('Y-m-d')] = $cq;
        }

        $plan    = [];
        $inicio  = $compromiso->getFechaInicio();
        $fin     = $compromiso->getFechaFin();
        $dia     = $compromiso->getDiaPago();
        $monto   = $compromiso->getMontoCuota();
        $numero  = 1;

        $cursor = new \DateTimeImmutable(sprintf('%04d-%02d-01', (int)$inicio->format('Y'), (int)$inicio->format('n')));
        $limite = $fin ?? $cursor->modify('+10 years');

        while ($cursor <= $limite) {
            $anio    = (int)$cursor->format('Y');
            $mes     = (int)$cursor->format('n');
            $diaReal = min($dia, (int)(new \DateTimeImmutable("{$anio}-{$mes}-01"))->format('t'));
            $fecha   = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $diaReal));

            if ($fecha >= $inicio->modify('first day of this month') && ($fin === null || $fecha <= $fin)) {
                $key   = $fecha->format('Y-m-d');
                $cuota = $cuotasPorFecha[$key] ?? null;
                $plan[] = [
                    'numero'   => $numero,
                    'fecha'    => $fecha->format('Y-m-d'),
                    'monto'    => $cuota?->getMontoTotal() ?? $monto,
                    'pagado'   => $cuota?->getMontoPagado() ?? 0.0,
                    'estado'   => $cuota?->getEstado() ?? 'futuro',
                    'cuotaId'  => $cuota?->getId(),
                ];
                $numero++;
            }

            $cursor = $cursor->modify('+1 month');
        }

        $totalPlan     = array_sum(array_column($plan, 'monto'));
        $totalPagado   = array_sum(array_column($plan, 'pagado'));
        $cuotasPagadas = count(array_filter($plan, fn($p) => $p['estado'] === 'pagado'));
        $pagosSinCuota = $this->pagoRepo->pagosSinCuotaPorCompromiso($tenantId)[$id] ?? 0.0;

        return $this->json([
            'compromiso' => [
                'id'         => $compromiso->getId(),
                'nombre'     => $compromiso->getNombre(),
                'tipo'       => $compromiso->getTipo(),
                'montoCuota' => $compromiso->getMontoCuota(),
                'diaPago'    => $compromiso->getDiaPago(),
                'fechaFin'   => $compromiso->getFechaFin()?->format('Y-m-d'),
                'descripcion'=> $compromiso->getDescripcion(),
            ],
            'pagosSinCuota' => round($pagosSinCuota, 2),
            'totalPlan'     => round($totalPlan, 2),
            'totalPagado'   => round($totalPagado, 2),
            'totalPendiente'=> round(max(0, $totalPlan - $totalPagado), 2),
            'cuotasPagadas' => $cuotasPagadas,
            'pctCompletado' => $totalPlan > 0 ? round(($totalPagado / $totalPlan) * 100, 1) : 0.0,
            'plan'          => $plan,
        ]);
    }

    // ── Pagar cuota ──────────────────────────────────────────────────────────

    #[Route('/cuota/{id}/pagar', name: 'cuota_pagar', methods: ['POST'])]
    public function pagarCuota(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $cuota = $this->cuotaRepo->find($id);
        if ($cuota === null || $cuota->getCompromiso()->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $monto  = (float) ($data['monto'] ?? $cuota->getSaldoPendiente());
        $fechaStr = $data['fecha'] ?? date('Y-m-d');
        try { $fecha = new \DateTimeImmutable($fechaStr); } catch (\Exception) { $fecha = new \DateTimeImmutable(); }

        // Registrar pago
        $pago = new FinPago();
        $pago->setTenantId($tenantId);
        $pago->setCuota($cuota);
        $pago->setCompromiso($cuota->getCompromiso());
        $pago->setFecha($fecha);
        $pago->setMonto($monto);
        $pago->setMoneda('HNL');
        $pago->setTasaCambio(1.0);
        $pago->setMontoHnl($monto);
        $pago->setMetodo((string) ($data['metodo'] ?? '') ?: null);
        $pago->setNota((string) ($data['nota'] ?? '') ?: null);
        $em->persist($pago);

        // Actualizar monto_pagado en la cuota
        $nuevoPagado = $cuota->getMontoPagado() + $monto;
        $cuota->setMontoPagado($nuevoPagado);

        $em->flush();

        return $this->json([
            'ok'          => true,
            'estado'      => $cuota->getEstado(),
            'montoPagado' => round($nuevoPagado, 2),
            'saldo'       => round($cuota->getSaldoPendiente(), 2),
        ], 201);
    }

    // ── Crear gasto ──────────────────────────────────────────────────────────

    #[Route('/gasto', name: 'gasto_crear', methods: ['POST'])]
    public function crearGasto(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $data    = json_decode($request->getContent(), true) ?? [];
        $bolsaId = (int) ($data['bolsaId'] ?? 0);
        $bolsa   = $this->bolsaRepo->find($bolsaId);

        if ($bolsa === null || $bolsa->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Bolsa no válida'], 422);
        }

        $fechaStr = $data['fecha'] ?? date('Y-m-d');
        try { $fecha = new \DateTimeImmutable($fechaStr); } catch (\Exception) { $fecha = new \DateTimeImmutable(); }

        $monto      = (float) ($data['monto'] ?? 0);
        $tasaCambio = max(0.00001, (float) ($data['tasaCambio'] ?? 1.0));
        $moneda     = (string) ($data['moneda'] ?? 'HNL');
        $montoHnl   = round($monto * $tasaCambio, 2);

        $gasto = new FinGasto();
        $gasto->setTenantId($tenantId);
        $gasto->setBolsa($bolsa);
        $gasto->setDescripcion((string) ($data['descripcion'] ?? ''));
        $gasto->setFecha($fecha);
        $gasto->setMonto($monto);
        $gasto->setMoneda($moneda);
        $gasto->setTasaCambio($tasaCambio);
        $gasto->setMontoHnl($montoHnl);
        $gasto->setNaturaleza((string) ($data['naturaleza'] ?? FinGasto::NATURALEZA_NECESIDAD));
        $gasto->setMetodo((string) ($data['metodo'] ?? '') ?: null);
        $gasto->setNota((string) ($data['nota'] ?? '') ?: null);

        $em->persist($gasto);
        $em->flush();

        return $this->json([
            'id'       => $gasto->getId(),
            'montoHnl' => $montoHnl,
            'bolsa'    => $bolsa->getNombre(),
        ], 201);
    }

    // ── Documentos ───────────────────────────────────────────────────────────

    #[Route('/cuota/{id}/documentos', name: 'cuota_documentos', methods: ['GET'])]
    public function documentosCuota(int $id): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $cuota = $this->cuotaRepo->find($id);
        if ($cuota === null || $cuota->getCompromiso()->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $docs = $this->documentoRepo->findByEntidad($tenantId, FinDocumento::ENTIDAD_CUOTA, $id);

        return $this->json(array_map(fn($d) => [
            'id'             => $d->getId(),
            'nombreOriginal' => $d->getNombreOriginal(),
            'tipoMime'       => $d->getTipoMime(),
            'tamano'         => $d->tamanoLegible(),
            'descripcion'    => $d->getDescripcion(),
            'esPdf'          => $d->esPdf(),
            'subidoAt'       => $d->getSubidoAt()->format('Y-m-d H:i'),
        ], $docs));
    }

    #[Route('/documento/subir', name: 'documento_subir', methods: ['POST'])]
    public function subirDocumento(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $archivo     = $request->files->get('archivo');
        $entidadTipo = (string) ($request->request->get('entidad_tipo') ?? 'cuota');
        $entidadId   = (int) ($request->request->get('entidad_id') ?? 0);
        $descripcion = trim((string) ($request->request->get('descripcion') ?? '')) ?: null;

        if ($archivo === null) {
            return $this->json(['error' => 'No se recibió archivo.'], 422);
        }

        // Validar que la entidad pertenece al tenant
        if ($entidadTipo === FinDocumento::ENTIDAD_CUOTA) {
            $cuota = $this->cuotaRepo->find($entidadId);
            if ($cuota === null || $cuota->getCompromiso()->getTenantId() !== $tenantId) {
                return $this->json(['error' => 'Cuota no válida.'], 404);
            }
        }

        $doc = new FinDocumento();
        $doc->setTenantId($tenantId);
        $doc->setEntidadTipo($entidadTipo);
        $doc->setEntidadId($entidadId);
        $doc->setDescripcion($descripcion);

        $error = $this->documentoStorage->subir($doc, $archivo);
        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        $em->persist($doc);
        $em->flush();

        return $this->json([
            'id'             => $doc->getId(),
            'nombreOriginal' => $doc->getNombreOriginal(),
            'tipoMime'       => $doc->getTipoMime(),
            'tamano'         => $doc->tamanoLegible(),
            'descripcion'    => $doc->getDescripcion(),
            'esPdf'          => $doc->esPdf(),
            'subidoAt'       => $doc->getSubidoAt()->format('Y-m-d H:i'),
        ], 201);
    }

    #[Route('/documento/{id}/ver', name: 'documento_ver', methods: ['GET'])]
    public function verDocumento(int $id): BinaryFileResponse|JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $doc = $this->documentoRepo->find($id);
        if ($doc === null || $doc->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $ruta = $this->documentoStorage->rutaAbsoluta($doc);
        if ($ruta === null) {
            return $this->json(['error' => 'Archivo no encontrado en disco.'], 404);
        }

        $response = new BinaryFileResponse($ruta);
        $response->headers->set('Content-Type', $doc->getTipoMime());
        $response->setContentDisposition(
            $doc->esPdf() ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $doc->getNombreOriginal()
        );

        return $response;
    }

    #[Route('/documento/{id}', name: 'documento_eliminar', methods: ['DELETE'])]
    public function eliminarDocumento(int $id, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $doc = $this->documentoRepo->find($id);
        if ($doc === null || $doc->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $this->documentoStorage->eliminarArchivo($doc);
        $em->remove($doc);
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
