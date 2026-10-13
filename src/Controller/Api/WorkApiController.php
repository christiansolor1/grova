<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Module\Personal\Work\Entity\WorkClient;
use App\Module\Personal\Work\Entity\WorkDay;
use App\Module\Personal\Work\Entity\WorkInvoice;
use App\Module\Personal\Work\Repository\WorkClientRepository;
use App\Module\Personal\Work\Repository\WorkDayRepository;
use App\Module\Personal\Work\Repository\WorkInvoiceRepository;
use App\Service\TenantContext;
use App\Service\WorkDayDocumentStorage;
use App\Service\WorkInvoicePaymentProofStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/work', name: 'api_work_')]
class WorkApiController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly WorkClientRepository $clientRepo,
        private readonly WorkDayRepository $dayRepo,
        private readonly WorkInvoiceRepository $invoiceRepo,
    ) {}

    #[Route('/cliente', name: 'cliente_get', methods: ['GET'])]
    public function obtenerCliente(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $client = $this->clientRepo->findActivo($tenantId);
        if ($client === null) {
            return $this->json(['error' => 'Sin cliente activo'], 404);
        }

        return $this->json($this->serializarCliente($client));
    }

    #[Route('/cliente', name: 'cliente_update', methods: ['PUT'])]
    public function actualizarCliente(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $client = $this->clientRepo->findActivo($tenantId);
        if ($client === null) {
            return $this->json(['error' => 'Sin cliente activo'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('nombre', $data))          $client->setNombre($data['nombre']);
        if (array_key_exists('cifNif', $data))          $client->setCifNif($data['cifNif']);
        if (array_key_exists('direccion', $data))       $client->setDireccion($data['direccion']);
        if (array_key_exists('emailsFactura', $data))   $client->setEmailsFactura($data['emailsFactura']);
        if (array_key_exists('salarioBase', $data))     $client->setSalarioBase((string) $data['salarioBase']);
        if (array_key_exists('bonusDia', $data))        $client->setBonusDia((string) $data['bonusDia']);
        if (array_key_exists('horaLimiteBonus', $data)) $client->setHoraLimiteBonus($data['horaLimiteBonus']);
        if (array_key_exists('bancNombre', $data))      $client->setBancNombre($data['bancNombre']);
        if (array_key_exists('bancSwift', $data))       $client->setBancSwift($data['bancSwift']);
        if (array_key_exists('bancCuenta', $data))      $client->setBancCuenta($data['bancCuenta']);
        if (array_key_exists('bancTitular', $data))     $client->setBancTitular($data['bancTitular']);
        if (array_key_exists('recargoHnl', $data)) {
            $client->setRecargoHnl($data['recargoHnl'] !== null ? (string) $data['recargoHnl'] : null);
        }

        $em->flush();

        return $this->json($this->serializarCliente($client));
    }

    private function serializarCliente(WorkClient $client): array
    {
        return [
            'id'              => $client->getId(),
            'nombre'          => $client->getNombre(),
            'cifNif'          => $client->getCifNif(),
            'direccion'       => $client->getDireccion(),
            'emailsFactura'   => $client->getEmailsFactura(),
            'salarioBase'     => $client->getSalarioBaseFloat(),
            'bonusDia'        => $client->getBonusDiaFloat(),
            'horaLimiteBonus' => $client->getHoraLimiteBonus(),
            'bancNombre'      => $client->getBancNombre(),
            'bancSwift'       => $client->getBancSwift(),
            'bancCuenta'      => $client->getBancCuenta(),
            'bancTitular'     => $client->getBancTitular(),
            'recargoHnl'      => $client->getRecargoHnlFloat(),
        ];
    }

    #[Route('/facturas', name: 'facturas_list', methods: ['GET'])]
    public function facturas(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $client = $this->clientRepo->findActivo($tenantId);
        if ($client === null) {
            return $this->json(['error' => 'Sin cliente activo'], 404);
        }

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $anioActual = (int) date('Y');
        $mesActual  = (int) date('n');

        // Calcular resumen del mes actual
        $diasActual = $this->dayRepo->findByMonth($tenantId, $anioActual, $mesActual);
        $trabajadosActual = 0;
        $bonusActual      = 0;
        $feriadosActual   = 0;
        $vacacionesActual = 0;
        foreach ($diasActual as $d) {
            if ($d->trabajado()) {
                $trabajadosActual++;
                if ($d->bonusAplica()) $bonusActual++;
            } elseif ($d->isEsFeriado()) {
                $feriadosActual++;
            } elseif ($d->isEsVacacion()) {
                $vacacionesActual++;
            }
        }
        $totalActual = $client->getSalarioBaseFloat() + ($bonusActual * $client->getBonusDiaFloat());

        // ¿Ya existe factura para el mes actual?
        $facturaActual = $this->invoiceRepo->findOneBy([
            'tenantId' => $tenantId,
            'client'   => $client,
            'anio'     => $anioActual,
            'mes'      => $mesActual,
        ]);

        $resultado = [];

        if ($facturaActual === null) {
            // Mes en curso sin factura
            $resultado[] = [
                'id'             => null,
                'anio'           => $anioActual,
                'mes'            => $mesActual,
                'mesNombre'      => $meses[$mesActual],
                'cliente'        => $client->getNombre(),
                'diasTrabajados' => $trabajadosActual,
                'diasFeriado'    => $feriadosActual,
                'diasVacacion'   => $vacacionesActual,
                'total'          => round($totalActual, 2),
                'estado'         => 'en_curso',
                'pagada'         => false,
                'pagadaAt'       => null,
                'enviadaAt'      => null,
                'numero'          => null,
                'lempirasRecibidos' => 0,
                'hasProof'        => false,
                'hasBankProof'    => false,
            ];
        }

        $facturas = $this->invoiceRepo->findLatest($tenantId, 24);
        foreach ($facturas as $f) {
            $diasMes    = $this->dayRepo->findByMonth($tenantId, $f->getAnio(), $f->getMes());
            $feriados   = 0;
            $vacaciones = 0;
            foreach ($diasMes as $d) {
                if ($d->isEsFeriado()) $feriados++;
                elseif ($d->isEsVacacion()) $vacaciones++;
            }

            $resultado[] = [
                'id'             => $f->getId(),
                'anio'           => $f->getAnio(),
                'mes'            => $f->getMes(),
                'mesNombre'      => $meses[$f->getMes()],
                'cliente'        => $f->getClient()->getNombre(),
                'diasTrabajados' => $f->getDiasTrabajados(),
                'diasFeriado'    => $feriados,
                'diasVacacion'   => $vacaciones,
                'total'          => $f->getTotalFloat(),
                'estado'         => $f->getEstado(),
                'pagada'         => $f->isPagada(),
                'pagadaAt'       => $f->getPagadaAt()?->format('Y-m-d'),
                'enviadaAt'      => $f->getEnviadaAt()?->format('Y-m-d'),
                'numero'          => $f->getNumero(),
                'lempirasRecibidos' => $f->getLempirasRecibidosFloat(),
                'hasProof'        => $f->hasPaymentProof(),
                'hasBankProof'    => $f->hasBankProof(),
            ];
        }

        return $this->json($resultado);
    }

    #[Route('/resumen', name: 'resumen', methods: ['GET'])]
    public function resumen(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $anio = (int) ($request->query->get('anio') ?? date('Y'));
        $mes  = (int) ($request->query->get('mes') ?? date('n'));

        $client = $this->clientRepo->findActivo($tenantId);
        if ($client === null) {
            return $this->json(['error' => 'Sin cliente activo'], 404);
        }

        $dias = $this->dayRepo->findByMonth($tenantId, $anio, $mes);

        $diasTrabajados = 0;
        $diasBonus      = 0;
        $diasFeriado    = 0;
        $diasVacacion   = 0;

        foreach ($dias as $dia) {
            if ($dia->trabajado()) {
                $diasTrabajados++;
                if ($dia->bonusAplica()) {
                    $diasBonus++;
                }
            } elseif ($dia->isEsFeriado()) {
                $diasFeriado++;
            } elseif ($dia->isEsVacacion()) {
                $diasVacacion++;
            }
        }

        $montoBonus = $diasBonus * $client->getBonusDiaFloat();
        $total      = $client->getSalarioBaseFloat() + $montoBonus;

        $invoice = $this->invoiceRepo->findOneBy([
            'tenantId' => $tenantId,
            'client'   => $client,
            'anio'     => $anio,
            'mes'      => $mes,
        ]);

        return $this->json([
            'anio'            => $anio,
            'mes'             => $mes,
            'cliente'         => $client->getNombre(),
            'diasTrabajados'  => $diasTrabajados,
            'diasBonus'       => $diasBonus,
            'diasFeriado'     => $diasFeriado,
            'diasVacacion'    => $diasVacacion,
            'salarioBase'     => $client->getSalarioBaseFloat(),
            'bonusDia'        => $client->getBonusDiaFloat(),
            'montoBonus'      => round($montoBonus, 2),
            'total'           => round($total, 2),
            'facturaEstado'   => $invoice?->getEstado(),
            'facturaPagada'   => $invoice?->isPagada() ?? false,
        ]);
    }

    #[Route('/facturas/{id}/comprobante', name: 'factura_comprobante_upload', methods: ['POST'])]
    public function subirComprobante(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        WorkInvoicePaymentProofStorage $storage,
    ): JsonResponse {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $factura = $this->invoiceRepo->find($id);
        if ($factura === null || $factura->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Factura no encontrada'], 404);
        }

        $archivo = $request->files->get('file');
        if ($archivo === null) {
            return $this->json(['error' => 'No se recibió archivo'], 422);
        }

        $error = $storage->tryReplaceProof($factura, $archivo);
        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        $em->flush();

        return $this->json([
            'hasProof'            => $factura->hasPaymentProof(),
            'paymentProofOriginalName' => $factura->getPaymentProofOriginalName(),
        ]);
    }

    #[Route('/facturas/{id}/comprobante', name: 'factura_comprobante_delete', methods: ['DELETE'])]
    public function eliminarComprobante(
        int $id,
        EntityManagerInterface $em,
        WorkInvoicePaymentProofStorage $storage,
    ): JsonResponse {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $factura = $this->invoiceRepo->find($id);
        if ($factura === null || $factura->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Factura no encontrada'], 404);
        }

        $storage->removeProof($factura);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/facturas/{id}/comprobante-banco', name: 'factura_comprobante_banco_upload', methods: ['POST'])]
    public function subirComprobanteBanco(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        WorkInvoicePaymentProofStorage $storage,
    ): JsonResponse {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $factura = $this->invoiceRepo->find($id);
        if ($factura === null || $factura->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Factura no encontrada'], 404);
        }

        $archivo = $request->files->get('file');
        if ($archivo === null) {
            return $this->json(['error' => 'No se recibió archivo'], 422);
        }

        $error = $storage->tryReplaceBankProof($factura, $archivo);
        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        $em->flush();

        return $this->json([
            'hasBankProof' => $factura->hasBankProof(),
            'bankProofOriginalName' => $factura->getBankProofOriginalName(),
        ]);
    }

    #[Route('/facturas/{id}/estado', name: 'factura_estado', methods: ['PUT'])]
    public function actualizarEstadoFactura(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $factura = $this->invoiceRepo->find($id);
        if ($factura === null || $factura->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Factura no encontrada'], 404);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $estado = $data['estado'] ?? null;

        if ($estado === 'enviada' && $factura->isBorrador()) {
            $factura->setEstado('enviada');
            $factura->setEnviadaAt(new \DateTimeImmutable());
        } elseif ($estado === 'pagada' && !$factura->isPagada()) {
            $factura->setPagadaAt(new \DateTimeImmutable());
            if (isset($data['lempirasRecibidos'])) {
                $factura->setLempirasRecibidos((string) $data['lempirasRecibidos']);
            }
        } else {
            return $this->json(['error' => 'Transición de estado no permitida'], 422);
        }

        $em->flush();

        return $this->json([
            'id'                 => $factura->getId(),
            'estado'             => $factura->getEstado(),
            'pagada'             => $factura->isPagada(),
            'pagadaAt'           => $factura->getPagadaAt()?->format('Y-m-d'),
            'enviadaAt'          => $factura->getEnviadaAt()?->format('Y-m-d'),
            'lempirasRecibidos'  => $factura->getLempirasRecibidosFloat(),
        ]);
    }

    #[Route('/dias', name: 'dias_list', methods: ['GET'])]
    public function dias(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $anio = (int) ($request->query->get('anio') ?? date('Y'));
        $mes  = (int) ($request->query->get('mes') ?? date('n'));

        $dias = $this->dayRepo->findByMonth($tenantId, $anio, $mes);

        $resultado = array_map(fn(WorkDay $d) => [
            'id'           => $d->getId(),
            'fecha'        => $d->getFecha()->format('Y-m-d'),
            'horaEntrada'  => $d->getHoraEntrada(),
            'esFeriado'    => $d->isEsFeriado(),
            'esVacacion'   => $d->isEsVacacion(),
            'esPermiso'    => $d->isEsPermiso(),
            'esIncapacidad'=> $d->isEsIncapacidad(),
            'hasDocumento' => $d->hasDocumento(),
            'trabajado'    => $d->trabajado(),
            'bonusAplica'  => $d->bonusAplica(),
            'notas'        => $d->getNotas(),
            'cliente'      => $d->getClient()->getNombre(),
        ], $dias);

        return $this->json($resultado);
    }

    #[Route('/dias/anio', name: 'dias_anio', methods: ['GET'])]
    public function diasAnio(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $anio = (int) ($request->query->get('anio') ?? date('Y'));
        $dias = $this->dayRepo->findByYear($tenantId, $anio);

        $feriados   = [];
        $vacaciones = [];

        foreach ($dias as $d) {
            if ($d->isEsFeriado()) {
                $feriados[] = $d->getFecha()->format('Y-m-d');
            } elseif ($d->isEsVacacion()) {
                $vacaciones[] = $d->getFecha()->format('Y-m-d');
            }
        }

        return $this->json([
            'feriados'   => $feriados,
            'vacaciones' => $vacaciones,
        ]);
    }

    #[Route('/dias/{id}', name: 'dias_update', methods: ['PUT'])]
    public function editarDia(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $dia = $this->dayRepo->find($id);
        if ($dia === null || $dia->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Día no encontrado'], 404);
        }

        $data          = json_decode($request->getContent(), true) ?? [];
        $esFeriado     = (bool) ($data['esFeriado']     ?? $dia->isEsFeriado());
        $esVacacion    = (bool) ($data['esVacacion']    ?? $dia->isEsVacacion());
        $esPermiso     = (bool) ($data['esPermiso']     ?? $dia->isEsPermiso());
        $esIncapacidad = (bool) ($data['esIncapacidad'] ?? $dia->isEsIncapacidad());
        $horaEntrada   = array_key_exists('horaEntrada', $data) ? $data['horaEntrada'] : $dia->getHoraEntrada();

        $dia->setEsFeriado($esFeriado);
        $dia->setEsVacacion($esVacacion);
        $dia->setEsPermiso($esPermiso);
        $dia->setEsIncapacidad($esIncapacidad);
        $dia->setHoraEntrada(
            $esFeriado || $esVacacion || $esPermiso || $esIncapacidad ? null : $horaEntrada
        );
        $dia->setNotas($data['notas'] ?? $dia->getNotas());

        $em->flush();

        return $this->json([
            'id'           => $dia->getId(),
            'fecha'        => $dia->getFecha()->format('Y-m-d'),
            'horaEntrada'  => $dia->getHoraEntrada(),
            'esFeriado'    => $dia->isEsFeriado(),
            'esVacacion'   => $dia->isEsVacacion(),
            'esPermiso'    => $dia->isEsPermiso(),
            'esIncapacidad'=> $dia->isEsIncapacidad(),
            'hasDocumento' => $dia->hasDocumento(),
            'trabajado'    => $dia->trabajado(),
            'bonusAplica'  => $dia->bonusAplica(),
            'notas'        => $dia->getNotas(),
            'cliente'      => $dia->getClient()->getNombre(),
        ]);
    }

    #[Route('/dias/{id}', name: 'dias_delete', methods: ['DELETE'])]
    public function eliminarDia(int $id, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $dia = $this->dayRepo->find($id);
        if ($dia === null || $dia->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Día no encontrado'], 404);
        }

        $em->remove($dia);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/dias', name: 'dias_create', methods: ['POST'])]
    public function crearDia(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $client = $this->clientRepo->findActivo($tenantId);
        if ($client === null) {
            return $this->json(['error' => 'Sin cliente activo'], 404);
        }

        $data          = json_decode($request->getContent(), true) ?? [];
        $fechaStr      = $data['fecha'] ?? date('Y-m-d');
        $horaEntrada   = $data['horaEntrada'] ?? null;
        $esFeriado     = (bool) ($data['esFeriado']     ?? false);
        $esVacacion    = (bool) ($data['esVacacion']    ?? false);
        $esPermiso     = (bool) ($data['esPermiso']     ?? false);
        $esIncapacidad = (bool) ($data['esIncapacidad'] ?? false);
        $notas         = $data['notas'] ?? null;

        // Verificar que no existe ya un día para esa fecha y cliente
        $existe = $this->dayRepo->findOneBy([
            'tenantId' => $tenantId,
            'client'   => $client,
            'fecha'    => new \DateTimeImmutable($fechaStr),
        ]);

        if ($existe !== null) {
            return $this->json(['error' => 'Ya existe un registro para esa fecha'], 409);
        }

        $dia = new WorkDay();
        $dia->setTenantId($tenantId);
        $dia->setClient($client);
        $dia->setFecha(new \DateTimeImmutable($fechaStr));
        $dia->setHoraEntrada(
            $esFeriado || $esVacacion || $esPermiso || $esIncapacidad ? null : $horaEntrada
        );
        $dia->setEsFeriado($esFeriado);
        $dia->setEsVacacion($esVacacion);
        $dia->setEsPermiso($esPermiso);
        $dia->setEsIncapacidad($esIncapacidad);
        $dia->setNotas($notas);

        $em->persist($dia);
        $em->flush();

        return $this->json([
            'id'           => $dia->getId(),
            'fecha'        => $dia->getFecha()->format('Y-m-d'),
            'horaEntrada'  => $dia->getHoraEntrada(),
            'esFeriado'    => $dia->isEsFeriado(),
            'esVacacion'   => $dia->isEsVacacion(),
            'esPermiso'    => $dia->isEsPermiso(),
            'esIncapacidad'=> $dia->isEsIncapacidad(),
            'hasDocumento' => $dia->hasDocumento(),
            'trabajado'    => $dia->trabajado(),
            'bonusAplica'  => $dia->bonusAplica(),
            'notas'        => $dia->getNotas(),
            'cliente'      => $dia->getClient()->getNombre(),
        ], 201);
    }

    #[Route('/facturas/{id}/comprobante', name: 'factura_comprobante_get', methods: ['GET'])]
    public function verComprobante(
        int $id,
        WorkInvoicePaymentProofStorage $storage,
    ): BinaryFileResponse {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            throw $this->createAccessDeniedException();
        }

        $factura = $this->invoiceRepo->find($id);
        if ($factura === null || $factura->getTenantId() !== $tenantId || !$factura->hasPaymentProof()) {
            throw $this->createNotFoundException();
        }

        $path = $storage->absolutePath($factura);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => $factura->getPaymentProofMime() ?? 'application/octet-stream',
        ], true, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/facturas/{id}/comprobante-banco', name: 'factura_comprobante_banco_get', methods: ['GET'])]
    public function verComprobanteBanco(
        int $id,
        WorkInvoicePaymentProofStorage $storage,
    ): BinaryFileResponse {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            throw $this->createAccessDeniedException();
        }

        $factura = $this->invoiceRepo->find($id);
        if ($factura === null || $factura->getTenantId() !== $tenantId || !$factura->hasBankProof()) {
            throw $this->createNotFoundException();
        }

        $path = $storage->absoluteBankProofPath($factura);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => $factura->getBankProofMime() ?? 'application/octet-stream',
        ], true, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/dias/{id}/documento', name: 'dia_documento_get', methods: ['GET'])]
    public function verDocumentoDia(
        int $id,
        WorkDayDocumentStorage $storage,
    ): BinaryFileResponse {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            throw $this->createAccessDeniedException();
        }

        $dia = $this->dayRepo->find($id);
        if ($dia === null || $dia->getTenantId() !== $tenantId || !$dia->hasDocumento()) {
            throw $this->createNotFoundException();
        }

        $path = $storage->absolutePath($dia);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => $dia->getDocumentoMime() ?? 'application/octet-stream',
        ], true, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/dias/{id}/documento', name: 'dia_documento_upload', methods: ['POST'])]
    public function subirDocumentoDia(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        WorkDayDocumentStorage $storage,
    ): JsonResponse {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $dia = $this->dayRepo->find($id);
        if ($dia === null || $dia->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Día no encontrado'], 404);
        }

        $archivo = $request->files->get('file');
        if ($archivo === null) {
            return $this->json(['error' => 'No se recibió archivo'], 422);
        }

        $error = $storage->tryReplaceDocumento($dia, $archivo);
        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        $em->flush();

        return $this->json([
            'hasDocumento'         => $dia->hasDocumento(),
            'documentoOriginalName' => $dia->getDocumentoOriginalName(),
        ]);
    }
}
