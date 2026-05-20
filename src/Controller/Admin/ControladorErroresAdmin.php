<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Admin\ServicioErrorLog;
use App\Service\MenuTreeBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/errores', name: 'grova_admin_errores_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class ControladorErroresAdmin extends AbstractController
{
    public function __construct(
        private readonly ServicioErrorLog $servicioErrorLog,
        private readonly MenuTreeBuilder $menuTreeBuilder,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function indice(Request $request): Response
    {
        $filtros = array_filter([
            'level' => $request->query->get('level'),
            'status' => $request->query->get('status'),
            'channel' => $request->query->get('channel'),
            'tenant_id' => $request->query->getInt('tenant_id') ?: null,
            'desde' => $request->query->get('desde'),
            'hasta' => $request->query->get('hasta'),
        ]);
        $resultado = $this->servicioErrorLog->listar($filtros, 1, 0);
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('admin/errores/indexErrores.html.twig', [
            'menu_tree' => $tree,
            'active_menu_key' => 'errores',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'errores' => $resultado['items'],
            'total' => $resultado['total'],
            'filtros' => $filtros,
            'niveles' => $this->servicioErrorLog->getNiveles(),
            'canales' => $this->servicioErrorLog->getCanales(),
        ]);
    }

    #[Route('/{id}', name: 'detalle', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detalle(int $id): Response
    {
        try {
            $error = $this->servicioErrorLog->obtener($id);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Error no encontrado.');
        }

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('admin/errores/detalleError.html.twig', [
            'menu_tree' => $tree,
            'active_menu_key' => 'errores',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'error' => $error,
        ]);
    }

    #[Route('/{id}/estado', name: 'cambiar_estado', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cambiarEstado(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('error_estado_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_errores_index');
        }

        $nuevoEstado = (string) $request->request->get('estado', 'new');

        try {
            $this->servicioErrorLog->cambiarEstado($id, $nuevoEstado);
            $this->addFlash('success', 'Estado actualizado correctamente.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('grova_admin_errores_detalle', ['id' => $id]);
    }
}
