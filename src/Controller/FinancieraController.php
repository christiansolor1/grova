<?php
declare(strict_types=1);
namespace App\Controller;

use App\Module\Personal\FinancialPlanning\Entity\FinCompromiso;
use App\Module\Personal\FinancialPlanning\Entity\FinCuota;
use App\Module\Personal\FinancialPlanning\Entity\FinDocumento;
use App\Module\Personal\FinancialPlanning\Entity\FinGasto;
use App\Module\Personal\FinancialPlanning\Entity\FinIngreso;
use App\Module\Personal\FinancialPlanning\Entity\FinAcreedor;
use App\Module\Personal\FinancialPlanning\Entity\FinInversion;
use App\Module\Personal\FinancialPlanning\Entity\FinMeta;
use App\Module\Personal\FinancialPlanning\Entity\FinPago;
use App\Module\Personal\FinancialPlanning\Repository\FinAcreedorRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinBolsaRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinDocumentoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinInversionRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinMetaRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinCompromisoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinCuotaRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinGastoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinIngresoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinPagoRepository;
use App\Module\Personal\FinancialPlanning\Service\FinCuotaGeneradorService;
use App\Module\Personal\FinancialPlanning\Service\FinDocumentoStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/financiera', name: 'grova_financiera_')]
final class FinancieraController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly FinCompromisoRepository $compromisoRepo,
        private readonly FinCuotaRepository $cuotaRepo,
        private readonly FinIngresoRepository $ingresoRepo,
        private readonly FinGastoRepository $gastoRepo,
        private readonly FinBolsaRepository $bolsaRepo,
        private readonly FinPagoRepository $pagoRepo,
        private readonly FinAcreedorRepository $acreedorRepo,
        private readonly FinMetaRepository $metaRepo,
        private readonly FinInversionRepository $inversionRepo,
        private readonly FinDocumentoRepository $documentoRepo,
        private readonly FinCuotaGeneradorService $cuotaGenerador,
        private readonly FinDocumentoStorage $documentoStorage,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $tree     = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        $anio = (int) ($request->query->get('anio') ?: date('Y'));
        $mes  = (int) ($request->query->get('mes')  ?: date('n'));

        $compromisos = $this->compromisoRepo->findActivosByTenant($tenantId);
        $proximas    = $this->cuotaRepo->findProximasByTenant($tenantId, 60);
        $ingresos    = $this->ingresoRepo->findByTenantMes($tenantId, $anio, $mes);
        $gastos      = $this->gastoRepo->findByTenantMes($tenantId, $anio, $mes);
        $bolsas      = $this->bolsaRepo->findActivasByTenant($tenantId);
        $metas        = $this->metaRepo->findByTenant($tenantId);
        $acreedores   = $this->acreedorRepo->findByTenant($tenantId);
        $inversiones       = $this->inversionRepo->findByTenant($tenantId);
        $invValorTotal     = $this->inversionRepo->sumValorActualByTenant($tenantId);
        $invInvertido      = $this->inversionRepo->sumInvertidoByTenant($tenantId);
        $documentos        = $this->documentoRepo->findAllByTenantGrouped($tenantId);
        $resumenCuotas     = $this->cuotaRepo->resumenPorCompromiso($tenantId);
        $pagosSinCuota     = $this->pagoRepo->pagosSinCuotaPorCompromiso($tenantId);

        // Totales del mes
        $totalIngresos = array_sum(array_map(fn($i) => $i->getNetoHnl(), $ingresos));
        $totalGastos   = array_sum(array_map(fn($g) => $g->getMontoHnl(), $gastos));

        // Gastos agrupados por bolsa
        $gastosPorBolsa = [];
        foreach ($gastos as $gasto) {
            $bid = $gasto->getBolsa()->getId();
            $gastosPorBolsa[$bid] = ($gastosPorBolsa[$bid] ?? 0) + $gasto->getMontoHnl();
        }

        // ── Salud financiera Babilonia ───────────────────────────────────────
        $salud = $this->calcularSalud($bolsas, $gastosPorBolsa, $totalIngresos, $ingresos);

        // ── Resumen general (histórico, independiente del mes) ───────────────
        $globalIngresos  = $this->ingresoRepo->sumNetoHnlByTenant($tenantId);
        $globalGastos    = $this->gastoRepo->sumMontoHnlByTenant($tenantId);
        $globalAhorro    = $this->ingresoRepo->sumAhorroByTenant($tenantId);
        $globalDeuda     = $this->acreedorRepo->sumSaldoActivosByTenant($tenantId);
        $globalSaldo     = round($globalIngresos - $globalGastos, 2);
        $cuotaMensual    = array_sum(array_map(
            fn($c) => $c->getMoneda() === 'HNL' ? $c->getMontoCuota() : 0.0,
            $compromisos
        ));

        return $this->render('workspace/pages/financiera/index.html.twig', [
            'menu_tree'       => $tree,
            'anio'            => $anio,
            'mes'             => $mes,
            'compromisos'     => $compromisos,
            'proximas'        => $proximas,
            'ingresos'        => $ingresos,
            'gastos'          => $gastos,
            'bolsas'          => $bolsas,
            'totalIngresos'   => $totalIngresos,
            'totalGastos'     => $totalGastos,
            'gastosPorBolsa'  => $gastosPorBolsa,
            'salud'           => $salud,
            'metas'           => $metas,
            'acreedores'      => $acreedores,
            'inversiones'     => $inversiones,
            'invValorTotal'   => $invValorTotal,
            'invInvertido'    => $invInvertido,
            // Global
            'globalIngresos'  => $globalIngresos,
            'globalGastos'    => $globalGastos,
            'globalAhorro'    => $globalAhorro,
            'globalDeuda'     => $globalDeuda,
            'globalSaldo'     => $globalSaldo,
            'cuotaMensual'    => $cuotaMensual,
            'documentos'      => $documentos,
            'resumenCuotas'   => $resumenCuotas,
            'pagosSinCuota'   => $pagosSinCuota,
        ]);
    }

    // ── Compromisos ─────────────────────────────────────────────────────────

    #[Route('/compromiso/crear', name: 'compromiso_crear', methods: ['POST'])]
    public function crearCompromiso(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('fin_compromiso', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $bolsaId  = (int) $request->request->get('bolsa_id');
        $bolsa    = $this->bolsaRepo->find($bolsaId);

        if ($bolsa === null || $bolsa->getTenantId() !== $tenantId) {
            $this->addFlash('danger', 'Bolsa no válida.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $fechaStr = trim((string) $request->request->get('fecha_inicio', date('Y-m-01')));
        try { $fechaInicio = new \DateTimeImmutable($fechaStr); } catch (\Exception) { $fechaInicio = new \DateTimeImmutable('first day of this month'); }

        $compromiso = new FinCompromiso();
        $compromiso->setTenantId($tenantId);
        $compromiso->setNombre(trim((string) $request->request->get('nombre', '')));
        $compromiso->setTipo((string) $request->request->get('tipo', FinCompromiso::TIPO_SERVICIO));
        $compromiso->setBolsa($bolsa);
        $compromiso->setMontoCuota((float) $request->request->get('monto_cuota', 0));
        $compromiso->setMoneda((string) $request->request->get('moneda', 'HNL'));
        $compromiso->setFrecuencia((string) $request->request->get('frecuencia', FinCompromiso::FRECUENCIA_MENSUAL));
        $compromiso->setDiaPago((int) $request->request->get('dia_pago', 1));
        $compromiso->setFechaInicio($fechaInicio);
        $compromiso->setDescripcion(trim((string) $request->request->get('descripcion', '')) ?: null);
        $compromiso->setContacto(trim((string) $request->request->get('contacto', '')) ?: null);

        $em->persist($compromiso);
        $em->flush();

        $this->addFlash('success', 'Compromiso creado.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/compromiso/{id}/toggle', name: 'compromiso_toggle', methods: ['POST'])]
    public function toggleCompromiso(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId   = $this->tenantContext->getTenant()->getId();
        $compromiso = $this->compromisoRepo->find($id);

        if ($compromiso === null || $compromiso->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_toggle_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $compromiso->setActivo(!$compromiso->isActivo());
        $em->flush();

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Pagos de cuotas ─────────────────────────────────────────────────────

    #[Route('/cuota/{id}/pagar', name: 'cuota_pagar', methods: ['POST'])]
    public function pagarCuota(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $cuota    = $this->cuotaRepo->find($id);

        if ($cuota === null || $cuota->getCompromiso()->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_pago_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $monto = (float) $request->request->get('monto', $cuota->getSaldoPendiente());
        $fechaStr = trim((string) $request->request->get('fecha', date('Y-m-d')));
        try { $fecha = new \DateTimeImmutable($fechaStr); } catch (\Exception) { $fecha = new \DateTimeImmutable(); }

        $pago = new FinPago();
        $pago->setTenantId($tenantId);
        $pago->setCuota($cuota);
        $pago->setCompromiso($cuota->getCompromiso());
        $pago->setFecha($fecha);
        $pago->setMonto($monto);
        $pago->setMoneda((string) $request->request->get('moneda', 'HNL'));
        $pago->setTasaCambio((float) $request->request->get('tasa_cambio', 1.0));
        $pago->setMontoHnl($monto * (float) $request->request->get('tasa_cambio', 1.0));
        $pago->setMetodo(trim((string) $request->request->get('metodo', '')) ?: null);
        $pago->setNota(trim((string) $request->request->get('nota', '')) ?: null);

        // Actualizar monto pagado en la cuota
        $cuota->setMontoPagado(round($cuota->getMontoPagado() + $monto, 2));

        $em->persist($pago);
        $em->flush();

        $this->addFlash('success', 'Pago registrado.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Ingresos ────────────────────────────────────────────────────────────

    #[Route('/ingreso/crear', name: 'ingreso_crear', methods: ['POST'])]
    public function crearIngreso(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('fin_ingreso', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $fechaStr = trim((string) $request->request->get('fecha', date('Y-m-d')));
        try { $fecha = new \DateTimeImmutable($fechaStr); } catch (\Exception) { $fecha = new \DateTimeImmutable(); }

        $montoBruto  = (float) $request->request->get('monto_bruto', 0);
        $comisiones  = (float) $request->request->get('comisiones', 0);
        $retenciones = (float) $request->request->get('retenciones', 0);
        $tasaCambio  = max(0.00001, (float) $request->request->get('tasa_cambio', 1.0));
        $moneda      = (string) $request->request->get('moneda', 'HNL');

        $netoOriginal = $montoBruto - $comisiones - $retenciones;
        $netoHnl      = round($netoOriginal * $tasaCambio, 2);

        // Babilonia: 10% automático si se activa
        $babilonia           = (bool) $request->request->get('babilonia', false);
        $ahorroReservadoHnl  = $babilonia ? round($netoHnl * 0.10, 2) : 0.0;

        $ingreso = new FinIngreso();
        $ingreso->setTenantId($tenantId);
        $ingreso->setFuente(trim((string) $request->request->get('fuente', '')));
        $ingreso->setFecha($fecha);
        $ingreso->setMontoBruto($montoBruto);
        $ingreso->setComisiones($comisiones);
        $ingreso->setRetenciones($retenciones);
        $ingreso->setMoneda($moneda);
        $ingreso->setTasaCambio($tasaCambio);
        $ingreso->setNetoHnl($netoHnl);
        $ingreso->setAhorroReservadoHnl($ahorroReservadoHnl);
        $ingreso->setNota(trim((string) $request->request->get('nota', '')) ?: null);

        $em->persist($ingreso);
        $em->flush();

        $this->addFlash('success', sprintf('Ingreso registrado. Neto: L %s%s', number_format($netoHnl, 2), $babilonia ? sprintf(' · Babilonia 10%%: L %s', number_format($ahorroReservadoHnl, 2)) : ''));
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/ingreso/{id}/delete', name: 'ingreso_delete', methods: ['POST'])]
    public function deleteIngreso(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $ingreso  = $this->ingresoRepo->find($id);

        if ($ingreso !== null && $ingreso->getTenantId() === $tenantId) {
            if ($this->isCsrfTokenValid('fin_ingreso_delete_' . $id, (string) $request->request->get('_token'))) {
                $em->remove($ingreso);
                $em->flush();
                $this->addFlash('success', 'Ingreso eliminado.');
            }
        }

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Gastos ──────────────────────────────────────────────────────────────

    #[Route('/gasto/crear', name: 'gasto_crear', methods: ['POST'])]
    public function crearGasto(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('fin_gasto', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $bolsaId  = (int) $request->request->get('bolsa_id');
        $bolsa    = $this->bolsaRepo->find($bolsaId);

        if ($bolsa === null || $bolsa->getTenantId() !== $tenantId) {
            $this->addFlash('danger', 'Bolsa no válida.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $fechaStr = trim((string) $request->request->get('fecha', date('Y-m-d')));
        try { $fecha = new \DateTimeImmutable($fechaStr); } catch (\Exception) { $fecha = new \DateTimeImmutable(); }

        $monto      = (float) $request->request->get('monto', 0);
        $tasaCambio = max(0.00001, (float) $request->request->get('tasa_cambio', 1.0));
        $moneda     = (string) $request->request->get('moneda', 'HNL');
        $montoHnl   = round($monto * $tasaCambio, 2);

        $gasto = new FinGasto();
        $gasto->setTenantId($tenantId);
        $gasto->setBolsa($bolsa);
        $gasto->setDescripcion(trim((string) $request->request->get('descripcion', '')));
        $gasto->setFecha($fecha);
        $gasto->setMonto($monto);
        $gasto->setMoneda($moneda);
        $gasto->setTasaCambio($tasaCambio);
        $gasto->setMontoHnl($montoHnl);
        $gasto->setNaturaleza((string) $request->request->get('naturaleza', FinGasto::NATURALEZA_NECESIDAD));
        $gasto->setMetodo(trim((string) $request->request->get('metodo', '')) ?: null);
        $gasto->setNota(trim((string) $request->request->get('nota', '')) ?: null);

        $em->persist($gasto);
        $em->flush();

        $this->addFlash('success', 'Gasto registrado.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/gasto/{id}/delete', name: 'gasto_delete', methods: ['POST'])]
    public function deleteGasto(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $gasto    = $this->gastoRepo->find($id);

        if ($gasto !== null && $gasto->getTenantId() === $tenantId) {
            if ($this->isCsrfTokenValid('fin_gasto_delete_' . $id, (string) $request->request->get('_token'))) {
                $em->remove($gasto);
                $em->flush();
                $this->addFlash('success', 'Gasto eliminado.');
            }
        }

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Salud financiera ────────────────────────────────────────────────────

    /**
     * Calcula indicadores de salud Babilonia/Harvard para el mes.
     *
     * @param \App\Module\Personal\FinancialPlanning\Entity\FinBolsa[] $bolsas
     * @param array<int,float> $gastosPorBolsa
     * @param \App\Module\Personal\FinancialPlanning\Entity\FinIngreso[] $ingresos
     * @return array<string, mixed>
     */
    private function calcularSalud(array $bolsas, array $gastosPorBolsa, float $totalIngresos, array $ingresos): array
    {
        // 10% Babilonia — suma de ahorros reservados en ingresos
        $ahorroReservado  = array_sum(array_map(fn($i) => $i->getAhorroReservadoHnl(), $ingresos));
        $objetivo10pct    = $totalIngresos > 0 ? round($totalIngresos * 0.10, 2) : 0.0;
        $cumpleBabilonia  = $totalIngresos > 0 && $ahorroReservado >= $objetivo10pct;

        // Por bolsa: % real vs % objetivo
        $bolsasData = [];
        $penalizacion = 0;

        foreach ($bolsas as $bolsa) {
            $gastado   = $gastosPorBolsa[$bolsa->getId()] ?? 0.0;
            $pctReal   = $totalIngresos > 0 ? round(($gastado / $totalIngresos) * 100, 1) : 0.0;
            $pctObj    = $bolsa->getPorcentajeSugerido();
            $desv      = $pctObj > 0 ? $pctReal - $pctObj : 0.0;

            // Estado semáforo
            if ($pctObj <= 0) {
                $estado = 'neutro'; // sin objetivo definido
            } elseif ($desv <= 2) {
                $estado = 'verde';
            } elseif ($desv <= 8) {
                $estado = 'amarillo';
                $penalizacion += 10;
            } else {
                $estado = 'rojo';
                $penalizacion += 20;
            }

            $bolsasData[] = [
                'bolsa'   => $bolsa,
                'gastado' => $gastado,
                'pctReal' => $pctReal,
                'pctObj'  => $pctObj,
                'desv'    => $desv,
                'estado'  => $estado,
            ];
        }

        if (!$cumpleBabilonia && $totalIngresos > 0) {
            $penalizacion += 25;
        }

        $puntuacion = max(0, 100 - $penalizacion);

        // Estado global
        if ($puntuacion >= 80) {
            $estadoGlobal = 'verde';
        } elseif ($puntuacion >= 50) {
            $estadoGlobal = 'amarillo';
        } else {
            $estadoGlobal = 'rojo';
        }

        return [
            'puntuacion'      => $puntuacion,
            'estadoGlobal'    => $estadoGlobal,
            'cumpleBabilonia' => $cumpleBabilonia,
            'ahorroReservado' => $ahorroReservado,
            'objetivo10pct'   => $objetivo10pct,
            'bolsasData'      => $bolsasData,
            'sinDatos'        => $totalIngresos <= 0,
        ];
    }

    // ── Metas de ahorro ─────────────────────────────────────────────────────

    #[Route('/meta/crear', name: 'meta_crear', methods: ['POST'])]
    public function crearMeta(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('fin_meta', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();

        $fechaStr = trim((string) $request->request->get('fecha_objetivo', ''));
        $fechaObjetivo = null;
        if ($fechaStr !== '') {
            try { $fechaObjetivo = new \DateTimeImmutable($fechaStr); } catch (\Exception) {}
        }

        $montoObjetivo = (float) $request->request->get('monto_objetivo', 0);
        $montoInicial  = (float) $request->request->get('monto_inicial', 0);

        // Calcular aporte mensual sugerido
        $mesesRestantes = 0;
        if ($fechaObjetivo !== null) {
            $hoy = new \DateTimeImmutable();
            $diff = $hoy->diff($fechaObjetivo);
            $mesesRestantes = max(0, $diff->y * 12 + $diff->m + ($diff->days > 0 ? 0 : 0));
            if ($fechaObjetivo > $hoy) {
                $mesesRestantes = (int) ceil(($fechaObjetivo->getTimestamp() - $hoy->getTimestamp()) / (30.44 * 86400));
            }
        }

        $meta = new FinMeta();
        $meta->setTenantId($tenantId);
        $meta->setNombre(trim((string) $request->request->get('nombre', '')));
        $meta->setTipo((string) $request->request->get('tipo', FinMeta::TIPO_OTRO));
        $meta->setMontoObjetivo($montoObjetivo);
        $meta->setMontoAcumulado($montoInicial);
        $meta->setFechaObjetivo($fechaObjetivo);

        $aporteSugerido = $mesesRestantes > 0
            ? round(max(0.0, $montoObjetivo - $montoInicial) / $mesesRestantes, 2)
            : 0.0;
        $meta->setAporteMensualSugerido($aporteSugerido);

        $em->persist($meta);
        $em->flush();

        $this->addFlash('success', 'Meta creada.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/meta/{id}/abonar', name: 'meta_abonar', methods: ['POST'])]
    public function abonarMeta(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $meta     = $this->metaRepo->find($id);

        if ($meta === null || $meta->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_meta_abonar_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $abono = (float) $request->request->get('abono', 0);
        if ($abono > 0) {
            $meta->setMontoAcumulado(round($meta->getMontoAcumulado() + $abono, 2));

            // Recalcular aporte sugerido con meses restantes
            if ($meta->getFechaObjetivo() !== null) {
                $hoy = new \DateTimeImmutable();
                if ($meta->getFechaObjetivo() > $hoy) {
                    $meses = (int) ceil(($meta->getFechaObjetivo()->getTimestamp() - $hoy->getTimestamp()) / (30.44 * 86400));
                    $meta->setAporteMensualSugerido($meta->calcularAporteMensual(max(1, $meses)));
                }
            }

            $em->flush();
            $this->addFlash('success', sprintf('Abono de L %s registrado en "%s".', number_format($abono, 2), $meta->getNombre()));
        }

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/meta/{id}/toggle', name: 'meta_toggle', methods: ['POST'])]
    public function toggleMeta(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $meta     = $this->metaRepo->find($id);

        if ($meta === null || $meta->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_meta_toggle_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $meta->setActiva(!$meta->isActiva());
        $em->flush();

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/meta/{id}/delete', name: 'meta_delete', methods: ['POST'])]
    public function deleteMeta(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $meta     = $this->metaRepo->find($id);

        if ($meta !== null && $meta->getTenantId() === $tenantId) {
            if ($this->isCsrfTokenValid('fin_meta_delete_' . $id, (string) $request->request->get('_token'))) {
                $em->remove($meta);
                $em->flush();
                $this->addFlash('success', 'Meta eliminada.');
            }
        }

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Acreedores ──────────────────────────────────────────────────────────

    #[Route('/acreedor/crear', name: 'acreedor_crear', methods: ['POST'])]
    public function crearAcreedor(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('fin_acreedor', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId   = $this->tenantContext->getTenant()->getId();
        $compromisoId = (int) $request->request->get('compromiso_id', 0);
        $compromiso   = $compromisoId > 0 ? $this->compromisoRepo->find($compromisoId) : null;

        $acreedor = new FinAcreedor();
        $acreedor->setTenantId($tenantId);
        $acreedor->setNombre(trim((string) $request->request->get('nombre', '')));
        $acreedor->setTipo((string) $request->request->get('tipo', FinAcreedor::TIPO_PRESTAMO));
        $acreedor->setSaldoHnl((float) $request->request->get('saldo_hnl', 0));
        $acreedor->setTasaAnual((float) $request->request->get('tasa_anual', 0) / 100);
        $acreedor->setCompromiso($compromiso !== null && $compromiso->getTenantId() === $tenantId ? $compromiso : null);

        $em->persist($acreedor);
        $em->flush();

        $this->addFlash('success', 'Acreedor registrado.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/acreedor/{id}/editar', name: 'acreedor_editar', methods: ['POST'])]
    public function editarAcreedor(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $acreedor = $this->acreedorRepo->find($id);

        if ($acreedor === null || $acreedor->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_acreedor_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $compromisoId = (int) $request->request->get('compromiso_id', 0);
        $compromiso   = $compromisoId > 0 ? $this->compromisoRepo->find($compromisoId) : null;

        $acreedor->setNombre(trim((string) $request->request->get('nombre', $acreedor->getNombre())));
        $acreedor->setTipo((string) $request->request->get('tipo', $acreedor->getTipo()));
        $acreedor->setSaldoHnl((float) $request->request->get('saldo_hnl', $acreedor->getSaldoHnl()));
        $acreedor->setTasaAnual((float) $request->request->get('tasa_anual', 0) / 100);
        $acreedor->setCompromiso($compromiso !== null && $compromiso->getTenantId() === $tenantId ? $compromiso : null);

        $em->flush();

        $this->addFlash('success', 'Acreedor actualizado.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/acreedor/{id}/toggle', name: 'acreedor_toggle', methods: ['POST'])]
    public function toggleAcreedor(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $acreedor = $this->acreedorRepo->find($id);

        if ($acreedor === null || $acreedor->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_acreedor_toggle_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $acreedor->setActivo(!$acreedor->isActivo());
        $em->flush();

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/acreedor/{id}/delete', name: 'acreedor_delete', methods: ['POST'])]
    public function deleteAcreedor(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $acreedor = $this->acreedorRepo->find($id);

        if ($acreedor !== null && $acreedor->getTenantId() === $tenantId) {
            if ($this->isCsrfTokenValid('fin_acreedor_delete_' . $id, (string) $request->request->get('_token'))) {
                $em->remove($acreedor);
                $em->flush();
                $this->addFlash('success', 'Acreedor eliminado.');
            }
        }

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Inversiones ─────────────────────────────────────────────────────────

    #[Route('/inversion/crear', name: 'inversion_crear', methods: ['POST'])]
    public function crearInversion(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('fin_inversion', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();

        $fechaStr = trim((string) $request->request->get('fecha_inicio', ''));
        $fechaInicio = null;
        if ($fechaStr !== '') {
            try { $fechaInicio = new \DateTimeImmutable($fechaStr); } catch (\Exception) {}
        }

        $montoInvertido = (float) $request->request->get('monto_invertido', 0);

        $inversion = new FinInversion();
        $inversion->setTenantId($tenantId);
        $inversion->setNombre(trim((string) $request->request->get('nombre', '')));
        $inversion->setTipo((string) $request->request->get('tipo', FinInversion::TIPO_OTRO));
        $inversion->setInstitucion(trim((string) $request->request->get('institucion', '')) ?: null);
        $inversion->setMontoInvertidoHnl($montoInvertido);
        $inversion->setValorActualHnl((float) $request->request->get('valor_actual', $montoInvertido));
        $inversion->setRendimientoEsperado((float) $request->request->get('rendimiento_esperado', 0) / 100);
        $inversion->setFechaInicio($fechaInicio);
        $inversion->setNota(trim((string) $request->request->get('nota', '')) ?: null);

        $em->persist($inversion);
        $em->flush();

        $this->addFlash('success', 'Inversión registrada.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/inversion/{id}/actualizar', name: 'inversion_actualizar', methods: ['POST'])]
    public function actualizarInversion(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId  = $this->tenantContext->getTenant()->getId();
        $inversion = $this->inversionRepo->find($id);

        if ($inversion === null || $inversion->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_inversion_upd_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $nuevoValor     = (float) $request->request->get('valor_actual', $inversion->getValorActualHnl());
        $nuevoInvertido = (float) $request->request->get('monto_invertido', $inversion->getMontoInvertidoHnl());

        $inversion->setValorActualHnl($nuevoValor);
        $inversion->setMontoInvertidoHnl($nuevoInvertido);
        $inversion->setNota(trim((string) $request->request->get('nota', '')) ?: null);

        // Calcular rendimiento real
        if ($nuevoInvertido > 0) {
            $rendReal = ($nuevoValor - $nuevoInvertido) / $nuevoInvertido;
            $inversion->setRendimientoReal(round($rendReal, 6));
        }

        $em->flush();

        $this->addFlash('success', sprintf('Inversión "%s" actualizada.', $inversion->getNombre()));
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/inversion/{id}/toggle', name: 'inversion_toggle', methods: ['POST'])]
    public function toggleInversion(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId  = $this->tenantContext->getTenant()->getId();
        $inversion = $this->inversionRepo->find($id);

        if ($inversion === null || $inversion->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_inversion_toggle_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $inversion->setActiva(!$inversion->isActiva());
        $em->flush();

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/inversion/{id}/delete', name: 'inversion_delete', methods: ['POST'])]
    public function deleteInversion(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId  = $this->tenantContext->getTenant()->getId();
        $inversion = $this->inversionRepo->find($id);

        if ($inversion !== null && $inversion->getTenantId() === $tenantId) {
            if ($this->isCsrfTokenValid('fin_inversion_delete_' . $id, (string) $request->request->get('_token'))) {
                $em->remove($inversion);
                $em->flush();
                $this->addFlash('success', 'Inversión eliminada.');
            }
        }

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Generador de cuotas ─────────────────────────────────────────────────

    #[Route('/cuotas/generar', name: 'cuotas_generar', methods: ['POST'])]
    public function generarCuotas(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('fin_generar_cuotas', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $anio     = (int) ($request->request->get('anio') ?: date('Y'));
        $mes      = (int) ($request->request->get('mes')  ?: date('n'));

        $resultado = $this->cuotaGenerador->generarMes($tenantId, $anio, $mes);

        if ($resultado['creadas'] > 0) {
            $this->addFlash('success', sprintf('%d cuota%s generada%s para %s.', $resultado['creadas'], $resultado['creadas'] !== 1 ? 's' : '', $resultado['creadas'] !== 1 ? 's' : '', \DateTimeImmutable::createFromFormat('Y-n', "{$anio}-{$mes}")->format('F Y')));
        } else {
            $this->addFlash('info', 'Las cuotas de este mes ya estaban generadas.');
        }

        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale(), 'anio' => $anio, 'mes' => $mes]);
    }

    // ── Editar compromiso ───────────────────────────────────────────────────

    #[Route('/compromiso/{id}/editar', name: 'compromiso_editar', methods: ['POST'])]
    public function editarCompromiso(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId   = $this->tenantContext->getTenant()->getId();
        $compromiso = $this->compromisoRepo->find($id);

        if ($compromiso === null || $compromiso->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('fin_compromiso_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $bolsaId = (int) $request->request->get('bolsa_id');
        $bolsa   = $this->bolsaRepo->find($bolsaId);
        if ($bolsa === null || $bolsa->getTenantId() !== $tenantId) {
            $this->addFlash('danger', 'Bolsa no válida.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $compromiso->setNombre(trim((string) $request->request->get('nombre', $compromiso->getNombre())));
        $compromiso->setTipo((string) $request->request->get('tipo', $compromiso->getTipo()));
        $compromiso->setBolsa($bolsa);
        $compromiso->setMontoCuota((float) $request->request->get('monto_cuota', $compromiso->getMontoCuota()));
        $compromiso->setMoneda((string) $request->request->get('moneda', $compromiso->getMoneda()));
        $compromiso->setFrecuencia((string) $request->request->get('frecuencia', $compromiso->getFrecuencia()));
        $compromiso->setDiaPago((int) $request->request->get('dia_pago', $compromiso->getDiaPago()));
        $compromiso->setDescripcion(trim((string) $request->request->get('descripcion', '')) ?: null);
        $compromiso->setContacto(trim((string) $request->request->get('contacto', '')) ?: null);

        $em->flush();

        $this->addFlash('success', 'Compromiso actualizado.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    // ── Plan de pago ────────────────────────────────────────────────────────

    #[Route('/compromiso/{id}/plan', name: 'compromiso_plan', methods: ['GET'])]
    public function planPago(int $id, Request $request): Response
    {
        $tenantId   = $this->tenantContext->getTenant()->getId();
        $compromiso = $this->compromisoRepo->find($id);
        $tree       = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        if ($compromiso === null || $compromiso->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }

        // Cuotas ya generadas en BD, indexadas por fecha Y-m-d
        $cuotasDb = $this->cuotaRepo->findByCompromiso($id);
        $cuotasPorFecha = [];
        foreach ($cuotasDb as $cq) {
            $cuotasPorFecha[$cq->getFechaVencimiento()->format('Y-m-d')] = $cq;
        }

        // Proyectar todas las fechas del plan aunque no estén generadas aún
        $plan = [];
        $inicio  = $compromiso->getFechaInicio();
        $fin     = $compromiso->getFechaFin();
        $dia     = $compromiso->getDiaPago();
        $monto   = $compromiso->getMontoCuota();
        $numero  = 1;

        $cursor = new \DateTimeImmutable(sprintf('%04d-%02d-01', (int)$inicio->format('Y'), (int)$inicio->format('n')));
        $limite = $fin ?? $cursor->modify('+10 years');

        while ($cursor <= $limite) {
            $anio = (int)$cursor->format('Y');
            $mes  = (int)$cursor->format('n');
            $diaReal = min($dia, (int)(new \DateTimeImmutable("{$anio}-{$mes}-01"))->format('t'));
            $fecha = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $diaReal));

            if ($fecha >= $inicio->modify('first day of this month') && ($fin === null || $fecha <= $fin)) {
                $key   = $fecha->format('Y-m-d');
                $cuota = $cuotasPorFecha[$key] ?? null;
                $plan[] = [
                    'numero'  => $numero,
                    'fecha'   => $fecha,
                    'monto'   => $cuota?->getMontoTotal() ?? $monto,
                    'pagado'  => $cuota?->getMontoPagado() ?? 0.0,
                    'estado'  => $cuota?->getEstado() ?? 'futuro',
                    'cuota'   => $cuota,
                ];
                $numero++;
            }

            $cursor = $cursor->modify('+1 month');
        }

        $totalPlan     = array_sum(array_column($plan, 'monto'));
        $totalPagado   = array_sum(array_column($plan, 'pagado'));
        $totalPendiente = $totalPlan - $totalPagado;
        $cuotasPagadas = count(array_filter($plan, fn($p) => $p['estado'] === 'pagado'));
        $pctCompletado = $totalPlan > 0 ? round(($totalPagado / $totalPlan) * 100, 1) : 0;

        // Documentos de cuotas generadas
        $docsPorCuota = [];
        foreach ($cuotasDb as $cq) {
            $docsPorCuota[$cq->getId()] = $this->documentoRepo->findByEntidad($tenantId, FinDocumento::ENTIDAD_CUOTA, $cq->getId());
        }

        $pagosSinCuotaComp = $this->pagoRepo->pagosSinCuotaPorCompromiso($tenantId)[$id] ?? 0.0;

        return $this->render('workspace/pages/financiera/planPago.html.twig', [
            'menu_tree'      => $tree,
            'compromiso'     => $compromiso,
            'plan'           => $plan,
            'totalPlan'      => $totalPlan,
            'totalPagado'    => $totalPagado,
            'totalPendiente' => $totalPendiente,
            'cuotasPagadas'  => $cuotasPagadas,
            'pctCompletado'  => $pctCompletado,
            'docsPorCuota'   => $docsPorCuota,
            'pagosSinCuota'  => $pagosSinCuotaComp,
        ]);
    }

    // ── Documentos ──────────────────────────────────────────────────────────

    #[Route('/documento/subir', name: 'documento_subir', methods: ['POST'])]
    public function subirDocumento(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('fin_documento', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId    = $this->tenantContext->getTenant()->getId();
        $entidadTipo = (string) $request->request->get('entidad_tipo', '');
        $entidadId   = (int) $request->request->get('entidad_id', 0);
        $descripcion = trim((string) $request->request->get('descripcion', '')) ?: null;

        $tiposValidos = [
            FinDocumento::ENTIDAD_COMPROMISO,
            FinDocumento::ENTIDAD_INVERSION,
            FinDocumento::ENTIDAD_ACREEDOR,
            FinDocumento::ENTIDAD_CUOTA,
        ];
        if (!\in_array($entidadTipo, $tiposValidos, true) || $entidadId <= 0) {
            $this->addFlash('danger', 'Entidad no válida.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $file = $request->files->get('archivo');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $this->addFlash('danger', 'No se recibió ningún archivo.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $doc = new FinDocumento();
        $doc->setTenantId($tenantId);
        $doc->setEntidadTipo($entidadTipo);
        $doc->setEntidadId($entidadId);
        $doc->setDescripcion($descripcion);

        $error = $this->documentoStorage->subir($doc, $file);
        if ($error !== null) {
            $this->addFlash('danger', $error);
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $em->persist($doc);
        $em->flush();

        $this->addFlash('success', 'Documento subido correctamente.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/documento/{id}/ver', name: 'documento_ver', methods: ['GET'])]
    public function verDocumento(int $id): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $doc      = $this->documentoRepo->find($id);

        if ($doc === null || $doc->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }

        $ruta = $this->documentoStorage->rutaAbsoluta($doc);
        if ($ruta === null) {
            throw $this->createNotFoundException('Archivo no encontrado en el servidor.');
        }

        $response = new BinaryFileResponse($ruta);
        $response->setContentDisposition(
            $doc->esPdf() ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $doc->getNombreOriginal()
        );
        $response->headers->set('Content-Type', $doc->getTipoMime());

        return $response;
    }

    #[Route('/documento/{id}/eliminar', name: 'documento_eliminar', methods: ['POST'])]
    public function eliminarDocumento(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $doc      = $this->documentoRepo->find($id);

        if ($doc === null || $doc->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('fin_doc_del_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
        }

        $this->documentoStorage->eliminarArchivo($doc);
        $em->remove($doc);
        $em->flush();

        $this->addFlash('success', 'Documento eliminado.');
        return $this->redirectToRoute('grova_financiera_index', ['_locale' => $request->getLocale()]);
    }
}
