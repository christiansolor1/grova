<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Wallet\Entity\WalletInstitucion;
use App\Module\Personal\Wallet\Repository\WalletCuentaRepository;
use App\Module\Personal\Wallet\Repository\WalletEntryRepository;
use App\Module\Personal\Wallet\Repository\WalletInstitucionRepository;
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
#[Route('/wallet/instituciones', name: 'grova_wallet_institucion_')]
final class WalletInstitucionController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly WalletInstitucionRepository $repo,
        private readonly WalletCuentaRepository $cuentaRepo,
        private readonly WalletEntryRepository $entryRepo,
        private readonly EncryptionService $encryption,
    ) {
    }

    /** Lista de instituciones para el select del modal cuenta */
    #[Route('/data', name: 'data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $items    = $this->repo->findByTenant($tenantId);

        $rows = array_map(fn(WalletInstitucion $i) => [
            'id'        => $i->getId(),
            'nombre'    => $i->getNombre(),
            'color'     => $i->getColor(),
            'editToken'   => $this->generateToken('wallet_institucion_edit_' . $i->getId()),
            'deleteToken' => $this->generateToken('wallet_institucion_delete_' . $i->getId()),
            'detalleUrl'  => $this->generateUrl('grova_wallet_institucion_detalle', ['id' => $i->getId()]),
        ], $items);

        return new JsonResponse(['instituciones' => $rows]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_institucion_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $nombre = trim((string) $request->request->get('nombre', ''));
        if ($nombre === '') {
            return new JsonResponse(['success' => false, 'message' => 'El nombre es obligatorio.'], 422);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();

        if ($this->repo->findOneByNombre($tenantId, $nombre) !== null) {
            return new JsonResponse(['success' => false, 'message' => 'Ya existe una institución con ese nombre.'], 422);
        }

        $inst = new WalletInstitucion();
        $inst->setTenantId($tenantId)
             ->setNombre($nombre)
             ->setColor((string) $request->request->get('color', '#64748b'));

        $em->persist($inst);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Institución creada.', 'id' => $inst->getId(), 'nombre' => $inst->getNombre(), 'color' => $inst->getColor()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_institucion_edit_' . $id, (string)$request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $inst     = $this->repo->find($id);

        if ($inst === null || $inst->getTenantId() !== $tenantId) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrada.'], 404);
        }

        $nombre = trim((string) $request->request->get('nombre', ''));
        if ($nombre === '') {
            return new JsonResponse(['success' => false, 'message' => 'El nombre es obligatorio.'], 422);
        }

        $existente = $this->repo->findOneByNombre($tenantId, $nombre);
        if ($existente !== null && $existente->getId() !== $id) {
            return new JsonResponse(['success' => false, 'message' => 'Ya existe una institución con ese nombre.'], 422);
        }

        $inst->setNombre($nombre)
             ->setColor((string) $request->request->get('color', $inst->getColor()));

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Institución actualizada.']);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_institucion_delete_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $inst     = $this->repo->find($id);

        if ($inst === null || $inst->getTenantId() !== $tenantId) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrada.'], 404);
        }

        $em->remove($inst);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Institución eliminada. Las cuentas asociadas quedaron sin institución.']);
    }

    /** Página de detalle: todas las cuentas de esta institución con tabs */
    #[Route('/{id}', name: 'detalle', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detalle(int $id, Request $request): Response
    {
        $meses    = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $tenantId = $this->tenantContext->getTenant()->getId();
        $inst     = $this->repo->find($id);

        if ($inst === null || $inst->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException('Institución no encontrada.');
        }

        $anio = (int) ($request->query->get('anio', (int) date('Y')));
        $mes  = max(1, min(12, (int) ($request->query->get('mes', (int) date('n')))));

        $cuentas     = $this->cuentaRepo->findByInstitucion($tenantId, $inst);
        $cuentasData = [];
        $totalSaldo  = 0.0;
        $allMovimientos = [];

        foreach ($cuentas as $cuenta) {
            $num   = $cuenta->getNumeroCuenta();
            $tar   = $cuenta->getTarjetaDigitos();
            $saldo = $this->cuentaRepo->getSaldo($cuenta);
            $movs  = $this->entryRepo->findByCuentaAndPeriodo($cuenta->getId(), $anio, $mes);

            $ingresos = 0.0;
            $gastos   = 0.0;
            $semanas  = array_fill_keys(['S1','S2','S3','S4'], ['ingreso' => 0.0, 'gasto' => 0.0]);

            foreach ($movs as $e) {
                if ($e->isIngreso()) { $ingresos += $e->getMontoFloat(); }
                else                 { $gastos   += $e->getMontoFloat(); }
                $dia = (int) $e->getFecha()->format('j');
                $sk  = $dia <= 7 ? 'S1' : ($dia <= 14 ? 'S2' : ($dia <= 21 ? 'S3' : 'S4'));
                $semanas[$sk][$e->getTipo()] += $e->getMontoFloat();
                $allMovimientos[] = ['entry' => $e, 'cuenta' => $cuenta];
            }

            $totalSaldo += $saldo;

            $cuentasData[] = [
                'cuenta'       => $cuenta,
                'numeroCuenta' => $num ? $this->encryption->decrypt($num) : null,
                'tarjeta'      => $tar ? $this->encryption->decrypt($tar) : null,
                'saldo'        => $saldo,
                'movimientos'  => $movs,
                'ingresos'     => $ingresos,
                'gastos'       => $gastos,
                'balance'      => $ingresos - $gastos,
                'semanas'      => $semanas,
            ];
        }

        usort($allMovimientos, fn($a, $b) =>
            $b['entry']->getFecha() <=> $a['entry']->getFecha() ?:
            $b['entry']->getCreatedAt() <=> $a['entry']->getCreatedAt()
        );

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('workspace/pages/wallet/institucionDetalle.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'wallet',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'institucion'             => $inst,
            'cuentas_data'            => $cuentasData,
            'all_movimientos'         => $allMovimientos,
            'total_saldo'             => $totalSaldo,
            'anio'                    => $anio,
            'mes'                     => $mes,
            'mes_label'               => $meses[$mes - 1] . ' ' . $anio,
            'meses'                   => $meses,
        ]);
    }

    private function generateToken(string $id): string
    {
        return $this->container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }
}
