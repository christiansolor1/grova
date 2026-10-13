<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Wallet\Entity\WalletCuenta;
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
#[Route('/wallet/cuentas', name: 'grova_wallet_cuenta_')]
final class WalletCuentaController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly WalletCuentaRepository $cuentaRepo,
        private readonly WalletEntryRepository $entryRepo,
        private readonly WalletInstitucionRepository $instRepo,
        private readonly EncryptionService $encryption,
    ) {
    }

    private function decryptCuenta(WalletCuenta $c): array
    {
        $num = $c->getNumeroCuenta();
        $tar = $c->getTarjetaDigitos();
        return [
            'numeroCuenta'   => $num ? $this->encryption->decrypt($num) : null,
            'tarjetaDigitos' => $tar ? $this->encryption->decrypt($tar) : null,
        ];
    }

    /** Lista activas para el select del sync Work → Wallet */
    #[Route('/activas', name: 'activas', methods: ['GET'])]
    public function activas(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $cuentas  = $this->cuentaRepo->findActivas($tenantId);

        $data = array_map(function(WalletCuenta $c) {
            $dec = $this->decryptCuenta($c);
            return [
                'id'              => $c->getId(),
                'nombre'          => $c->getNombre(),
                'label'           => $c->getLabel(),
                'numeroCuenta'    => $dec['numeroCuenta'],
                'institucionId'   => $c->getInstitucion()?->getId(),
                'institucionNombre' => $c->getInstitucionNombre(),
                'tipo'            => $c->getTipo(),
                'tipoLabel'       => $c->getTipoLabel(),
                'color'           => $c->getColor(),
            ];
        }, $cuentas);

        return new JsonResponse(['cuentas' => $data]);
    }

    /** Lista completa (activas + inactivas) con saldo — para la tabla en Wallet */
    #[Route('/data', name: 'data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $cuentas  = $this->cuentaRepo->findAll_($tenantId);

        $rows = [];
        foreach ($cuentas as $cuenta) {
            $saldo = $this->cuentaRepo->getSaldo($cuenta);
            $dec   = $this->decryptCuenta($cuenta);
            $rows[] = [
                'id'             => $cuenta->getId(),
                'nombre'         => htmlspecialchars($cuenta->getNombre(), ENT_QUOTES, 'UTF-8'),
                'label'          => htmlspecialchars($cuenta->getLabel(), ENT_QUOTES, 'UTF-8'),
                'institucionId'      => $cuenta->getInstitucion()?->getId(),
                'institucionNombre'  => htmlspecialchars($cuenta->getInstitucionNombre() ?? '—', ENT_QUOTES, 'UTF-8'),
                'numeroCuenta'   => $dec['numeroCuenta'],
                'tarjetaDigitos' => $dec['tarjetaDigitos'],
                'tipo'           => $cuenta->getTipoLabel(),
                'tipoRaw'        => $cuenta->getTipo(),
                'color'          => $cuenta->getColor(),
                'saldo'          => $saldo,
                'saldoFmt'       => 'L ' . number_format(abs($saldo), 2, '.', ','),
                'activo'         => $cuenta->isActivo(),
                'editToken'      => $this->generateToken('wallet_cuenta_edit_' . $cuenta->getId()),
                'deleteToken'    => $this->generateToken('wallet_cuenta_delete_' . $cuenta->getId()),
            ];
        }

        return new JsonResponse(['data' => $rows]);
    }

    #[Route('/{id}', name: 'detalle', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detalle(int $id, Request $request): Response
    {
        $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $tenantId = $this->tenantContext->getTenant()->getId();
        $cuenta   = $this->cuentaRepo->find($id);

        if ($cuenta === null || $cuenta->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException('Cuenta no encontrada.');
        }

        $anio = (int) ($request->query->get('anio', (int) date('Y')));
        $mes  = max(1, min(12, (int) ($request->query->get('mes', (int) date('n')))));

        $dec         = $this->decryptCuenta($cuenta);
        $saldo       = $this->cuentaRepo->getSaldo($cuenta);
        $movimientos = $this->entryRepo->findByCuentaAndPeriodo($id, $anio, $mes);

        $ingresos = 0.0;
        $gastos   = 0.0;
        $semanas  = array_fill_keys(['S1','S2','S3','S4'], ['ingreso' => 0.0, 'gasto' => 0.0]);

        foreach ($movimientos as $e) {
            if ($e->isIngreso()) { $ingresos += $e->getMontoFloat(); }
            else                 { $gastos   += $e->getMontoFloat(); }

            $dia = (int) $e->getFecha()->format('j');
            $sk  = $dia <= 7 ? 'S1' : ($dia <= 14 ? 'S2' : ($dia <= 21 ? 'S3' : 'S4'));
            $semanas[$sk][$e->getTipo()] += $e->getMontoFloat();
        }

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('workspace/pages/wallet/cuentaDetalle.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'wallet',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'cuenta'                  => $cuenta,
            'numeroCuenta'            => $dec['numeroCuenta'],
            'tarjeta'                 => $dec['tarjetaDigitos'],
            'saldo'                   => $saldo,
            'movimientos'             => $movimientos,
            'ingresos'                => $ingresos,
            'gastos'                  => $gastos,
            'balance'                 => $ingresos - $gastos,
            'anio'                    => $anio,
            'mes'                     => $mes,
            'mes_label'               => $meses[$mes - 1] . ' ' . $anio,
            'semanas'                 => $semanas,
            'meses'                   => $meses,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_cuenta_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $nombre = trim((string) $request->request->get('nombre', ''));
        $tipo   = (string) $request->request->get('tipo', 'debito');

        if ($nombre === '' || !in_array($tipo, WalletCuenta::TIPOS, true)) {
            return new JsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 422);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();

        $instId = $request->request->get('institucion_id', '');
        $inst   = ($instId !== '' && $instId !== null) ? $this->instRepo->find((int) $instId) : null;

        $cuenta = new WalletCuenta();
        $cuenta->setTenantId($tenantId);
        $cuenta->setNombre($nombre);
        $cuenta->setInstitucion(($inst !== null && $inst->getTenantId() === $tenantId) ? $inst : null);
        $numCuenta = trim((string) $request->request->get('numero_cuenta', ''));
        $cuenta->setNumeroCuenta($numCuenta !== '' ? $this->encryption->encrypt($numCuenta) : null);
        $tarjeta = trim((string) $request->request->get('tarjeta_digitos', ''));
        $cuenta->setTarjetaDigitos($tarjeta !== '' ? $this->encryption->encrypt($tarjeta) : null);
        $cuenta->setTipo($tipo);
        $cuenta->setSaldoInicial(number_format(max(0.0, (float) $request->request->get('saldo_inicial', '0')), 2, '.', ''));
        $cuenta->setColor((string) $request->request->get('color', '#64748b'));
        $cuenta->setActivo(true);

        $em->persist($cuenta);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Cuenta creada.', 'id' => $cuenta->getId()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_cuenta_edit_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $cuenta = $this->cuentaRepo->find($id);
        if ($cuenta === null) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrada.'], 404);
        }

        $nombre = trim((string) $request->request->get('nombre', ''));
        $tipo   = (string) $request->request->get('tipo', $cuenta->getTipo());

        if ($nombre === '' || !in_array($tipo, WalletCuenta::TIPOS, true)) {
            return new JsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 422);
        }

        $instIdEdit = $request->request->get('institucion_id', '');
        $instEdit   = ($instIdEdit !== '' && $instIdEdit !== null) ? $this->instRepo->find((int) $instIdEdit) : null;

        $cuenta->setNombre($nombre);
        $cuenta->setInstitucion(($instEdit !== null && $instEdit->getTenantId() === $this->tenantContext->getTenant()->getId()) ? $instEdit : null);
        $numCuentaEdit = trim((string) $request->request->get('numero_cuenta', ''));
        $cuenta->setNumeroCuenta($numCuentaEdit !== '' ? $this->encryption->encrypt($numCuentaEdit) : null);
        $tarjetaEdit = trim((string) $request->request->get('tarjeta_digitos', ''));
        $cuenta->setTarjetaDigitos($tarjetaEdit !== '' ? $this->encryption->encrypt($tarjetaEdit) : null);
        $cuenta->setTipo($tipo);
        $cuenta->setSaldoInicial(number_format(max(0.0, (float) $request->request->get('saldo_inicial', '0')), 2, '.', ''));
        $cuenta->setColor((string) $request->request->get('color', $cuenta->getColor()));
        $cuenta->setActivo((bool) $request->request->get('activo', true));

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Cuenta actualizada.']);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_cuenta_delete_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $cuenta = $this->cuentaRepo->find($id);
        if ($cuenta === null) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrada.'], 404);
        }

        $em->remove($cuenta);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Cuenta eliminada.']);
    }

    private function generateToken(string $id): string
    {
        return $this->container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }
}
