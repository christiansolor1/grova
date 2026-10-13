<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Wallet\Entity\WalletEntry;
use App\Module\Personal\Wallet\Repository\WalletCategoryRepository;
use App\Module\Personal\Wallet\Repository\WalletCuentaRepository;
use App\Module\Personal\Wallet\Repository\WalletEntryRepository;
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
#[Route('/wallet', name: 'grova_wallet_')]
final class WalletController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly WalletEntryRepository $entryRepo,
        private readonly WalletCategoryRepository $categoryRepo,
        private readonly WalletCuentaRepository $cuentaRepo,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $now      = new \DateTimeImmutable();
        $year     = (int) $now->format('Y');
        $month    = (int) $now->format('n');
        $tenantId = $this->tenantContext->getTenant()->getId();

        $tree       = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $categories = $this->categoryRepo->findAllOrdered();
        $cuentas    = $this->cuentaRepo->findActivas($tenantId);

        $saldo       = $this->entryRepo->getSaldoTotal();
        $ingresosMes = $this->entryRepo->getSumByTipoAndMonth('ingreso', $year, $month);
        $gastosMes   = $this->entryRepo->getSumByTipoAndMonth('gasto', $year, $month);

        return $this->render('workspace/pages/wallet/indexWallet.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'wallet',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'categories'              => $categories,
            'cuentas'                 => $cuentas,
            'saldo'                   => $saldo,
            'ingresos_mes'            => $ingresosMes,
            'gastos_mes'              => $gastosMes,
            'mes_label'               => $now->format('F Y'),
        ]);
    }

    /** Movimientos para DataTable AJAX */
    #[Route('/entries/data', name: 'entries_data', methods: ['GET'])]
    public function entriesData(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $entries  = $this->entryRepo->findLatest(200);

        $rows = [];
        foreach ($entries as $e) {
            $catHtml = $e->getCategory()
                ? '<span class="cat-chip"><i class="' . htmlspecialchars($e->getCategory()->getIcono(), ENT_QUOTES, 'UTF-8') . '" style="color:' . htmlspecialchars($e->getCategory()->getColor(), ENT_QUOTES, 'UTF-8') . ';"></i> ' . htmlspecialchars($e->getCategory()->getNombre(), ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span style="font-size:12px;color:#475569;">Sin categoría</span>';

            $cuentaHtml = $e->getCuenta()
                ? '<span class="cuenta-chip" style="border-color:' . htmlspecialchars($e->getCuenta()->getColor(), ENT_QUOTES, 'UTF-8') . ';">' . htmlspecialchars($e->getCuenta()->getNombre(), ENT_QUOTES, 'UTF-8') . '</span>'
                : '<span class="text-muted" style="font-size:11px;">Cartera</span>';

            $signo    = $e->isIngreso() ? '+' : '-';
            $montoStr = $signo . 'L ' . number_format($e->getMontoFloat(), 2, '.', ',');

            $deleteForm = '<form method="post" action="#" class="js-wallet-entry-delete mb-0"'
                . ' data-action="' . $this->generateUrl('grova_wallet_entry_delete', ['id' => $e->getId()]) . '"'
                . ' data-token="' . $this->generateToken('wallet_delete_' . $e->getId()) . '">'
                . '<button type="submit" class="btn btn-outline-danger btn-sm" style="padding:2px 7px;font-size:11px;"><i class="bi bi-trash3"></i></button>'
                . '</form>';

            $rows[] = [
                'fecha'      => $e->getFecha()->format('d/m/Y'),
                'fecha_sort' => $e->getFecha()->format('Y-m-d'),
                'desc'       => htmlspecialchars($e->getDescripcion() ?? '—', ENT_QUOTES, 'UTF-8'),
                'categoria'  => $catHtml,
                'cuenta'     => $cuentaHtml,
                'tipo'       => $e->isIngreso()
                    ? '<span class="badge bg-success bg-opacity-20 text-success" style="font-size:10px;">INGRESO</span>'
                    : '<span class="badge bg-danger bg-opacity-20 text-danger" style="font-size:10px;">GASTO</span>',
                'monto'      => '<span class="fw-semibold ' . ($e->isIngreso() ? 'tipo-ingreso' : 'tipo-gasto') . '">' . $montoStr . '</span>',
                'monto_sort' => $e->getMontoSignado(),
                'ops'        => $deleteForm,
            ];
        }

        return new JsonResponse(['data' => $rows]);
    }

    #[Route('/entry/create', name: 'entry_create', methods: ['POST'])]
    public function createEntry(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_entry', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $tipo    = (string) $request->request->get('tipo', 'gasto');
        $monto   = (float) $request->request->get('monto', '0');
        $fechaStr = (string) $request->request->get('fecha', date('Y-m-d'));

        if (!in_array($tipo, ['ingreso', 'gasto'], true) || $monto <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'El monto debe ser mayor a 0.'], 422);
        }

        $tenantId   = $this->tenantContext->getTenant()->getId();
        $descripcion = trim((string) $request->request->get('descripcion', ''));
        $categoryId  = $request->request->get('category_id');
        $cuentaId    = $request->request->get('cuenta_id');

        $entry = new WalletEntry();
        $entry->setTenantId($tenantId);
        $entry->setTipo($tipo);
        $entry->setMonto(number_format(abs($monto), 2, '.', ''));
        $entry->setDescripcion($descripcion !== '' ? $descripcion : null);
        $entry->setFecha(new \DateTimeImmutable($fechaStr));

        if ($categoryId !== null && $categoryId !== '') {
            $cat = $this->categoryRepo->find((int) $categoryId);
            if ($cat !== null) {
                $entry->setCategory($cat);
            }
        }

        if ($cuentaId !== null && $cuentaId !== '') {
            $cuenta = $this->cuentaRepo->find((int) $cuentaId);
            if ($cuenta !== null && $cuenta->getTenantId() === $tenantId) {
                $entry->setCuenta($cuenta);
            }
        }

        $em->persist($entry);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => ($tipo === 'ingreso' ? 'Ingreso' : 'Gasto') . ' de L ' . number_format(abs($monto), 2) . ' registrado.',
        ]);
    }

    #[Route('/entry/{id}/delete', name: 'entry_delete', methods: ['POST'])]
    public function deleteEntry(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_delete_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $entry = $this->entryRepo->find($id);
        if ($entry !== null) {
            $em->remove($entry);
            $em->flush();
        }

        return new JsonResponse(['success' => true, 'message' => 'Movimiento eliminado.']);
    }

    #[Route('/banca', name: 'banca', methods: ['GET'])]
    public function banca(): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $cuentas  = $this->cuentaRepo->findAll_($tenantId);

        $grupos     = [];  // ['institucion' => WalletInstitucion|null, 'cuentas' => [...], 'saldo' => float]
        $totalSaldo = 0.0;

        foreach ($cuentas as $cuenta) {
            $saldo      = $this->cuentaRepo->getSaldo($cuenta);
            $totalSaldo += $cuenta->isCredito() ? -$saldo : $saldo;

            $instId = $cuenta->getInstitucion()?->getId() ?? 0;

            if (!isset($grupos[$instId])) {
                $grupos[$instId] = [
                    'institucion' => $cuenta->getInstitucion(),
                    'cuentas'     => [],
                    'saldo'       => 0.0,
                ];
            }

            $grupos[$instId]['cuentas'][] = [
                'cuenta' => $cuenta,
                'saldo'  => $saldo,
            ];
            $grupos[$instId]['saldo'] += $cuenta->isCredito() ? -$saldo : $saldo;
        }

        // Instituciones primero, cartera/efectivo (null) al final
        uasort($grupos, static function ($a, $b) {
            if ($a['institucion'] === null) { return 1; }
            if ($b['institucion'] === null) { return -1; }
            return strcmp($a['institucion']->getNombre(), $b['institucion']->getNombre());
        });

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('workspace/pages/wallet/bancaWallet.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'wallet',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'grupos'                  => array_values($grupos),
            'total_saldo'             => $totalSaldo,
        ]);
    }

    private function generateToken(string $id): string
    {
        return $this->container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }
}
