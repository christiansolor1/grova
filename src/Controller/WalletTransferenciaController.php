<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Wallet\Entity\WalletEntry;
use App\Module\Personal\Wallet\Entity\WalletTransferencia;
use App\Module\Personal\Wallet\Repository\WalletCuentaRepository;
use App\Module\Personal\Wallet\Repository\WalletTransferenciaRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/wallet/transferencias', name: 'grova_wallet_transferencia_')]
final class WalletTransferenciaController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly WalletTransferenciaRepository $repo,
        private readonly WalletCuentaRepository $cuentaRepo,
    ) {
    }

    #[Route('/data', name: 'data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $tenantId      = $this->tenantContext->getTenant()->getId();
        $transferencias = $this->repo->findByTenant($tenantId);

        $rows = array_map(fn(WalletTransferencia $t) => [
            'id'            => $t->getId(),
            'fecha'         => $t->getFecha()->format('d/m/Y'),
            'cuentaOrigen'  => $t->getNombreOrigen(),
            'cuentaDestino' => $t->getNombreDestino(),
            'monto'         => $t->getMontoFloat(),
            'montoFmt'      => 'L ' . number_format($t->getMontoFloat(), 2, '.', ','),
            'comision'      => $t->getComisionFloat() > 0 ? $t->getComisionFloat() : null,
            'comisionFmt'   => $t->getComisionFloat() > 0 ? 'L ' . number_format($t->getComisionFloat(), 2, '.', ',') : null,
            'concepto'      => $t->getConcepto(),
            'tokenRevertir' => $this->generateToken('wallet_transferencia_revertir_' . $t->getId()),
        ], $transferencias);

        return new JsonResponse(['transferencias' => $rows]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_transferencia_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $monto = (float) $request->request->get('monto', '0');
        if ($monto <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'El monto debe ser mayor a 0.'], 422);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();

        $origenId  = $request->request->get('cuenta_origen_id', '');
        $destinoId = $request->request->get('cuenta_destino_id', '');

        if ($origenId === '' && $destinoId === '') {
            return new JsonResponse(['success' => false, 'message' => 'Origen y destino no pueden ser ambos Cartera general.'], 422);
        }

        if ($origenId !== '' && $origenId === $destinoId) {
            return new JsonResponse(['success' => false, 'message' => 'Origen y destino no pueden ser la misma cuenta.'], 422);
        }

        $cuentaOrigen  = $origenId !== '' ? $this->cuentaRepo->find((int) $origenId) : null;
        $cuentaDestino = $destinoId !== '' ? $this->cuentaRepo->find((int) $destinoId) : null;

        if (($origenId !== '' && ($cuentaOrigen === null || $cuentaOrigen->getTenantId() !== $tenantId)) ||
            ($destinoId !== '' && ($cuentaDestino === null || $cuentaDestino->getTenantId() !== $tenantId))) {
            return new JsonResponse(['success' => false, 'message' => 'Cuenta no válida.'], 422);
        }

        $fechaStr = (string) $request->request->get('fecha', date('Y-m-d'));
        try {
            $fecha = new \DateTimeImmutable($fechaStr);
        } catch (\Exception) {
            return new JsonResponse(['success' => false, 'message' => 'Fecha inválida.'], 422);
        }

        $montoFmt  = number_format($monto, 2, '.', '');
        $nombreOrigen  = $cuentaOrigen?->getNombre() ?? 'Cartera general';
        $nombreDestino = $cuentaDestino?->getNombre() ?? 'Cartera general';

        // Entrada de gasto en origen
        $entrySalida = new WalletEntry();
        $entrySalida->setTenantId($tenantId)
            ->setTipo('gasto')
            ->setEsTransferencia(true)
            ->setMonto($montoFmt)
            ->setCuenta($cuentaOrigen)
            ->setDescripcion('Transferencia → ' . $nombreDestino)
            ->setFecha($fecha);
        $em->persist($entrySalida);

        // Entrada de ingreso en destino
        $entryEntrada = new WalletEntry();
        $entryEntrada->setTenantId($tenantId)
            ->setTipo('ingreso')
            ->setEsTransferencia(true)
            ->setMonto($montoFmt)
            ->setCuenta($cuentaDestino)
            ->setDescripcion('Transferencia ← ' . $nombreOrigen)
            ->setFecha($fecha);
        $em->persist($entryEntrada);

        // Comisión (opcional)
        $comision     = (float) $request->request->get('comision', '0');
        $entryComision = null;
        if ($comision > 0) {
            $entryComision = new WalletEntry();
            $entryComision->setTenantId($tenantId)
                ->setTipo('gasto')
                ->setMonto(number_format($comision, 2, '.', ''))
                ->setCuenta($cuentaOrigen)
                ->setDescripcion('Comisión bancaria')
                ->setFecha($fecha);
            $em->persist($entryComision);
        }

        $concepto = trim((string) $request->request->get('concepto', '')) ?: null;

        $transferencia = new WalletTransferencia();
        $transferencia->setTenantId($tenantId)
            ->setFecha($fecha)
            ->setMonto($montoFmt)
            ->setConcepto($concepto)
            ->setCuentaOrigen($cuentaOrigen)
            ->setCuentaDestino($cuentaDestino)
            ->setComision($comision > 0 ? number_format($comision, 2, '.', '') : null)
            ->setEntrySalida($entrySalida)
            ->setEntryEntrada($entryEntrada)
            ->setEntryComision($entryComision);

        $em->persist($transferencia);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Transferencia registrada.',
            'id'      => $transferencia->getId(),
        ]);
    }

    #[Route('/{id}/revertir', name: 'revertir', methods: ['POST'])]
    public function revertir(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_transferencia_revertir_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $transferencia = $this->repo->find($id);
        if ($transferencia === null || $transferencia->getTenantId() !== $this->tenantContext->getTenant()->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrada.'], 404);
        }

        $entrySalida  = $transferencia->getEntrySalida();
        $entryEntrada = $transferencia->getEntryEntrada();
        $entryComision = $transferencia->getEntryComision();

        $em->remove($transferencia);
        $em->flush();

        // Las entradas se eliminan por CASCADE desde la DB, pero por si acaso:
        if ($em->contains($entrySalida))  { $em->remove($entrySalida);  }
        if ($em->contains($entryEntrada)) { $em->remove($entryEntrada); }
        if ($entryComision !== null && $em->contains($entryComision)) { $em->remove($entryComision); }
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Transferencia revertida.']);
    }

    private function generateToken(string $id): string
    {
        return $this->container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }
}
