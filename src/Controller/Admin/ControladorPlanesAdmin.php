<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Admin\ServicioPlanesAdmin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/planes', name: 'grova_admin_planes_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class ControladorPlanesAdmin extends AbstractController
{
    public function __construct(
        private readonly ServicioPlanesAdmin $servicioPlanes,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function indice(): Response
    {
        return $this->render('admin/planes/indexPlanes.html.twig', [
            'planes' => $this->servicioPlanes->listarPlanes(),
        ]);
    }

    #[Route('/crear', name: 'crear', methods: ['GET'])]
    public function crear(): Response
    {
        return $this->render('admin/planes/form.html.twig', [
            'plan' => null,
            'modulosDisponibles' => $this->servicioPlanes->obtenerModulosDisponibles(),
        ]);
    }

    #[Route('/crear', name: 'guardar', methods: ['POST'])]
    public function guardar(Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('crear_plan', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_planes_index');
        }

        $nombre = trim((string) $peticion->request->get('nombre', ''));
        /** @var list<string> $modulos */
        $modulos = array_values(array_filter((array) $peticion->request->all('modulos')));
        $precio = (string) $peticion->request->get('precio_mensual', '0');

        if ($nombre === '') {
            $this->addFlash('error', 'El nombre del plan no puede estar vacío.');

            return $this->redirectToRoute('grova_admin_planes_index');
        }

        if (!is_numeric($precio) || (float) $precio < 0) {
            $this->addFlash('error', 'El precio debe ser un número válido mayor o igual a 0.');

            return $this->redirectToRoute('grova_admin_planes_index');
        }

        try {
            $this->servicioPlanes->crearPlan($nombre, $modulos, $precio);
            $this->addFlash('success', 'Plan creado correctamente.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('grova_admin_planes_index');
    }

    #[Route('/{id}/editar', name: 'editar', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editar(int $id): Response
    {
        try {
            $plan = $this->servicioPlanes->obtenerPlan($id);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Plan no encontrado.');
        }

        return $this->render('admin/planes/form.html.twig', [
            'plan' => $plan,
            'modulosDisponibles' => $this->servicioPlanes->obtenerModulosDisponibles(),
        ]);
    }

    #[Route('/{id}/editar', name: 'actualizar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function actualizar(int $id, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('editar_plan_'.$id, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_planes_index');
        }

        $nombre = trim((string) $peticion->request->get('nombre', ''));
        /** @var list<string> $modulos */
        $modulos = array_values(array_filter((array) $peticion->request->all('modulos')));
        $precio = (string) $peticion->request->get('precio_mensual', '0');

        if ($nombre === '') {
            $this->addFlash('error', 'El nombre del plan no puede estar vacío.');

            return $this->redirectToRoute('grova_admin_planes_index');
        }

        if (!is_numeric($precio) || (float) $precio < 0) {
            $this->addFlash('error', 'El precio debe ser un número válido mayor o igual a 0.');

            return $this->redirectToRoute('grova_admin_planes_index');
        }

        try {
            $this->servicioPlanes->actualizarPlan($id, $nombre, $modulos, $precio);
            $this->addFlash('success', 'Plan actualizado correctamente.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('grova_admin_planes_index');
    }

    #[Route('/{id}/estado', name: 'alternar_estado', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function alternarEstado(int $id, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('plan_estado_'.$id, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_planes_index');
        }

        try {
            $this->servicioPlanes->alternarEstadoPlan($id);
            $this->addFlash('success', 'Estado del plan actualizado.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('grova_admin_planes_index');
    }
}
