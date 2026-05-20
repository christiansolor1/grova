<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Tenant;
use App\Repository\TenantRepository;
use App\Service\Admin\ServicioPlanesAdmin;
use App\Service\Admin\ServicioSuscripcionesAdmin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/empresas/{id}/suscripciones', name: 'grova_admin_suscripciones_', requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class ControladorSuscripcionesAdmin extends AbstractController
{
    public function __construct(
        private readonly ServicioSuscripcionesAdmin $servicioSuscripciones,
        private readonly ServicioPlanesAdmin $servicioPlanes,
        private readonly TenantRepository $repositorioInquilinos,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function indice(int $id): Response
    {
        $inquilino = $this->obtenerInquilino($id);

        return $this->render('admin/suscripciones/indexSuscripciones.html.twig', [
            'inquilino' => $inquilino,
            'historial' => $this->servicioSuscripciones->listarHistorial($id),
            'suscripcionActiva' => $this->servicioSuscripciones->obtenerSuscripcionActiva($id),
            'planes' => $this->servicioPlanes->listarPlanesActivos(),
        ]);
    }

    #[Route('/asignar', name: 'asignar', methods: ['POST'])]
    public function asignar(int $id, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('asignar_plan_'.$id, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_suscripciones_index', ['id' => $id]);
        }

        $planId = (int) $peticion->request->get('plan_id');
        $tipoCliente = $peticion->request->get('tipo_cliente');
        $fechaVencimientoRaw = $peticion->request->get('fecha_vencimiento');

        if ($planId < 1) {
            $this->addFlash('error', 'Debes seleccionar un plan.');

            return $this->redirectToRoute('grova_admin_suscripciones_index', ['id' => $id]);
        }

        $fechaVencimiento = null;
        if ($fechaVencimientoRaw !== '' && $fechaVencimientoRaw !== null) {
            $fechaVencimiento = new \DateTimeImmutable($fechaVencimientoRaw);
        }

        try {
            $this->servicioSuscripciones->asignarPlan(
                idTenant: $id,
                idPlan: $planId,
                tipoCliente: $tipoCliente,
                fechaVencimiento: $fechaVencimiento,
            );
            $this->addFlash('success', 'Plan asignado correctamente.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('grova_admin_suscripciones_index', ['id' => $id]);
    }

    private function obtenerInquilino(int $id): Tenant
    {
        $inquilino = $this->repositorioInquilinos->find($id);

        if (!$inquilino instanceof Tenant) {
            throw $this->createNotFoundException('Empresa no encontrada.');
        }

        return $inquilino;
    }
}
