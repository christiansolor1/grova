<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Construccion\Entity\ConstruccionGasto;
use App\Module\Construccion\Entity\ConstruccionObra;
use App\Module\Construccion\Entity\ConstruccionProveedor;
use App\Module\Construccion\Repository\ConstruccionGastoRepository;
use App\Module\Construccion\Repository\ConstruccionObraRepository;
use App\Module\Construccion\Repository\ConstruccionProveedorRepository;
use App\Module\Core\Contact\Repository\ContactRepository;
use App\Service\MenuTreeBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/construccion', name: 'grova_construccion_')]
final class ConstruccionController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly ConstruccionObraRepository $obraRepo,
        private readonly ConstruccionProveedorRepository $proveedorRepo,
        private readonly ConstruccionGastoRepository $gastoRepo,
        private readonly ContactRepository $contactRepo,
    ) {
    }

    private function treeVars(): array
    {
        return [
            'menu_tree'               => $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER')),
            'active_menu_key'         => 'construccion',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ];
    }

    // ── Index ────────────────────────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $obras      = $this->obraRepo->findAllOrdered();
        $stats      = $this->obraRepo->countByEstado();
        $proveedores = $this->proveedorRepo->findActivos();

        return $this->render('workspace/pages/construccion/index.html.twig', array_merge($this->treeVars(), [
            'obras'       => $obras,
            'stats'       => $stats,
            'proveedores' => $proveedores,
        ]));
    }

    // ── Obras ────────────────────────────────────────────────────────────────

    #[Route('/obra/create', name: 'obra_create', methods: ['POST'])]
    public function createObra(
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('construccion_obra', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_construccion_index', ['_locale' => $request->getLocale()]);
        }

        $obra = new ConstruccionObra();
        $this->fillObraFromRequest($obra, $request);

        $em->persist($obra);
        $em->flush();

        $this->addFlash('success', sprintf('Obra "%s" creada.', $obra->getNombre()));
        return $this->redirectToRoute('grova_construccion_obra_show', ['id' => $obra->getId(), '_locale' => $request->getLocale()]);
    }

    #[Route('/obra/{id}', name: 'obra_show', methods: ['GET'])]
    public function showObra(int $id): Response
    {
        $obra = $this->obraRepo->find($id);
        if ($obra === null) { throw $this->createNotFoundException(); }

        $gastos    = $this->gastoRepo->findByObra($obra);
        $byCateg   = $this->gastoRepo->totalsByCategoriaForObra($obra);
        $proveedores = $this->proveedorRepo->findActivos();

        return $this->render('workspace/pages/construccion/obra.html.twig', array_merge($this->treeVars(), [
            'obra'        => $obra,
            'gastos'      => $gastos,
            'by_categ'    => $byCateg,
            'proveedores' => $proveedores,
        ]));
    }

    #[Route('/obra/{id}/edit', name: 'obra_edit', methods: ['POST'])]
    public function editObra(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('construccion_obra_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_construccion_obra_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $obra = $this->obraRepo->find($id);
        if ($obra === null) { throw $this->createNotFoundException(); }

        $this->fillObraFromRequest($obra, $request);
        $em->flush();

        $this->addFlash('success', 'Obra actualizada.');
        return $this->redirectToRoute('grova_construccion_obra_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }

    private function fillObraFromRequest(ConstruccionObra $obra, Request $request): void
    {
        $obra->setNombre(trim((string) $request->request->get('nombre', '')));
        $obra->setDescripcion(trim((string) $request->request->get('descripcion', '')) ?: null);
        $obra->setEstado((string) $request->request->get('estado', 'activa'));
        $obra->setNotas(trim((string) $request->request->get('notas', '')) ?: null);

        $presupuesto = $request->request->get('presupuesto');
        $obra->setPresupuesto($presupuesto !== null && $presupuesto !== '' ? number_format(abs((float) $presupuesto), 2, '.', '') : null);

        $fechaInicio = trim((string) $request->request->get('fecha_inicio', ''));
        $obra->setFechaInicio($fechaInicio !== '' ? new \DateTimeImmutable($fechaInicio) : null);

        $fechaFin = trim((string) $request->request->get('fecha_fin', ''));
        $obra->setFechaFin($fechaFin !== '' ? new \DateTimeImmutable($fechaFin) : null);

        $contactoId = (int) $request->request->get('cliente_id', 0);
        if ($contactoId > 0) {
            $contacto = $this->contactRepo->find($contactoId);
            $obra->setCliente($contacto);
            $obra->setClienteNombre(null);
        } else {
            $obra->setCliente(null);
            $clienteNombre = trim((string) $request->request->get('cliente_nombre', ''));
            $obra->setClienteNombre($clienteNombre ?: null);
        }
    }

    // ── Gastos ───────────────────────────────────────────────────────────────

    #[Route('/obra/{id}/gasto/add', name: 'gasto_add', methods: ['POST'])]
    public function addGasto(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('construccion_gasto_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_construccion_obra_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $obra = $this->obraRepo->find($id);
        if ($obra === null) { throw $this->createNotFoundException(); }

        $gasto = new ConstruccionGasto();
        $gasto->setObra($obra);
        $gasto->setCategoria((string) $request->request->get('categoria', 'material'));
        $gasto->setDescripcion(trim((string) $request->request->get('descripcion', '')));
        $gasto->setMonto(abs((float) $request->request->get('monto', 0)));
        $gasto->setEstado((string) $request->request->get('estado', 'pendiente'));
        $gasto->setNotas(trim((string) $request->request->get('notas', '')) ?: null);

        $fechaStr = trim((string) $request->request->get('fecha', date('Y-m-d')));
        $gasto->setFecha(new \DateTimeImmutable($fechaStr));

        $proveedorId = (int) $request->request->get('proveedor_id', 0);
        if ($proveedorId > 0) {
            $proveedor = $this->proveedorRepo->find($proveedorId);
            $gasto->setProveedor($proveedor);
        }

        $em->persist($gasto);
        $em->flush();

        $this->addFlash('success', sprintf('Gasto "%s" registrado — L %.2f', $gasto->getDescripcion(), $gasto->getMonto()));
        return $this->redirectToRoute('grova_construccion_obra_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }

    #[Route('/gasto/{id}/delete', name: 'gasto_delete', methods: ['POST'])]
    public function deleteGasto(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('construccion_gasto_del_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_construccion_index', ['_locale' => $request->getLocale()]);
        }

        $gasto = $this->gastoRepo->find($id);
        if ($gasto === null) { throw $this->createNotFoundException(); }

        $obraId = $gasto->getObra()->getId();
        $em->remove($gasto);
        $em->flush();

        $this->addFlash('success', 'Gasto eliminado.');
        return $this->redirectToRoute('grova_construccion_obra_show', ['id' => $obraId, '_locale' => $request->getLocale()]);
    }

    #[Route('/gasto/{id}/pagar', name: 'gasto_pagar', methods: ['POST'])]
    public function pagarGasto(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('construccion_gasto_pagar_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_construccion_index', ['_locale' => $request->getLocale()]);
        }

        $gasto = $this->gastoRepo->find($id);
        if ($gasto === null) { throw $this->createNotFoundException(); }

        $gasto->setEstado('pagado');
        $em->flush();

        $obraId = $gasto->getObra()->getId();
        $this->addFlash('success', 'Gasto marcado como pagado.');
        return $this->redirectToRoute('grova_construccion_obra_show', ['id' => $obraId, '_locale' => $request->getLocale()]);
    }

    // ── Proveedores ──────────────────────────────────────────────────────────

    #[Route('/proveedor/create', name: 'proveedor_create', methods: ['POST'])]
    public function createProveedor(
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('construccion_proveedor', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_construccion_index', ['_locale' => $request->getLocale()]);
        }

        $p = new ConstruccionProveedor();
        $p->setNombre(trim((string) $request->request->get('nombre', '')));
        $p->setTelefono(trim((string) $request->request->get('telefono', '')) ?: null);
        $p->setEspecialidad((string) $request->request->get('especialidad', 'materiales'));
        $p->setNotas(trim((string) $request->request->get('notas', '')) ?: null);

        $em->persist($p);
        $em->flush();

        $this->addFlash('success', sprintf('Proveedor "%s" agregado.', $p->getNombre()));
        return $this->redirectToRoute('grova_construccion_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/proveedor/{id}/edit', name: 'proveedor_edit', methods: ['POST'])]
    public function editProveedor(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('construccion_proveedor_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_construccion_index', ['_locale' => $request->getLocale()]);
        }

        $p = $this->proveedorRepo->find($id);
        if ($p === null) { throw $this->createNotFoundException(); }

        $p->setNombre(trim((string) $request->request->get('nombre', '')));
        $p->setTelefono(trim((string) $request->request->get('telefono', '')) ?: null);
        $p->setEspecialidad((string) $request->request->get('especialidad', 'materiales'));
        $p->setNotas(trim((string) $request->request->get('notas', '')) ?: null);
        $p->setActivo($request->request->has('activo'));

        $em->flush();
        $this->addFlash('success', 'Proveedor actualizado.');
        return $this->redirectToRoute('grova_construccion_index', ['_locale' => $request->getLocale()]);
    }
}
