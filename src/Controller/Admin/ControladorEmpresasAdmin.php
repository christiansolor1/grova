<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Admin\ServicioEmpresasAdmin;
use App\Service\Admin\ServicioPlanesAdmin;
use App\Service\Admin\ServicioProvisionadorOrganizaciones;
use App\Service\Admin\ServicioSuscripcionesAdmin;
use App\Service\MenuTreeBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'grova_admin_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class ControladorEmpresasAdmin extends AbstractController
{
    public function __construct(
        private readonly ServicioEmpresasAdmin $servicioEmpresas,
        private readonly ServicioProvisionadorOrganizaciones $servicioProvisionador,
        private readonly ServicioPlanesAdmin $servicioPlanes,
        private readonly ServicioSuscripcionesAdmin $servicioSuscripciones,
        private readonly MenuTreeBuilder $menuTreeBuilder,
    ) {
    }

    #[Route('', name: 'empresas', methods: ['GET'])]
    public function indice(): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('admin/empresas/indexOrganizaciones.html.twig', [
            'empresas' => $this->servicioEmpresas->listarEmpresas(),
            'menu_tree' => $tree,
            'active_menu_key' => 'tenants',
        ]);
    }

    #[Route('/empresas/crear', name: 'empresa_crear', methods: ['GET'])]
    public function crear(): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('admin/empresas/formCrearOrganizacion.html.twig', [
            'planes' => $this->servicioPlanes->listarPlanesActivos(),
            'menu_tree' => $tree,
            'active_menu_key' => 'tenants',
        ]);
    }

    #[Route('/empresas/crear', name: 'empresa_guardar', methods: ['POST'])]
    public function guardar(Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('crear_empresa', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_empresas');
        }

        try {
            $inquilino = $this->servicioProvisionador->crear(
                (string) $peticion->request->get('nombre', ''),
                (int) $peticion->request->get('plan_id', 0),
                (string) $peticion->request->get('estado', 'activo'),
                $peticion->request->get('tipo'),
                $peticion->request->get('admin_email'),
                $peticion->request->get('admin_username'),
                $peticion->request->get('admin_password'),
                $peticion->request->get('admin_nombre'),
                $peticion->request->get('admin_apellido'),
            );
            $this->addFlash('success', sprintf('Organización «%s» creada correctamente.', $inquilino->getNombre()));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('grova_admin_empresa_crear');
        }

        return $this->redirectToRoute('grova_admin_empresa_editar', ['id' => $inquilino->getId()]);
    }

    #[Route('/empresas/{id}/editar', name: 'empresa_editar', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editar(int $id): Response
    {
        try {
            $inquilino = $this->servicioEmpresas->obtenerInquilino($id);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException();
        }

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('admin/empresas/formOrganizacion.html.twig', [
            'inquilino' => $inquilino,
            'suscripcionActiva' => $this->servicioSuscripciones->obtenerSuscripcionActiva($id),
            'menu_tree' => $tree,
            'active_menu_key' => 'tenants',
        ]);
    }

    #[Route('/empresas/{id}/editar', name: 'empresa_actualizar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function actualizar(int $id, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('editar_empresa_'.$id, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_empresas');
        }

        try {
            $this->servicioEmpresas->actualizarOrganizacion(
                $id,
                (string) $peticion->request->get('nombre', ''),
                (string) $peticion->request->get('estado', 'activo'),
                $peticion->request->get('tipo'),
            );
            $this->addFlash('success', 'Organización actualizada.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('grova_admin_empresa_editar', ['id' => $id]);
        }

        return $this->redirectToRoute('grova_admin_empresa_editar', ['id' => $id]);
    }

    #[Route('/empresas/{id}/tipo', name: 'empresa_tipo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function setTipo(int $id, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('admin_tipo_'.$id, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_empresas');
        }

        try {
            $this->servicioEmpresas->setTipoEmpresa($id, $peticion->request->get('tipo'));
            $this->addFlash('success', 'Tipo de empresa actualizado.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $volver = (string) $peticion->request->get('_volver', '');
        if ($volver === 'editar') {
            return $this->redirectToRoute('grova_admin_empresa_editar', ['id' => $id]);
        }

        return $this->redirectToRoute('grova_admin_empresas');
    }

    #[Route('/empresas/{id}/estado', name: 'empresa_estado', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function alternarEstado(int $id, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('admin_empresa_estado_'.$id, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_empresas');
        }

        try {
            $this->servicioEmpresas->alternarEstadoEmpresa($id);
            $this->addFlash('success', 'Estado de la empresa actualizado.');
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', 'Empresa no encontrada.');
        }

        $volver = (string) $peticion->request->get('_volver', '');
        if ($volver === 'editar') {
            return $this->redirectToRoute('grova_admin_empresa_editar', ['id' => $id]);
        }

        return $this->redirectToRoute('grova_admin_empresas');
    }
}
