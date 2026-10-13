<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Residencial\Entity\Casa;
use App\Module\Residencial\Entity\Manzana;
use App\Module\Residencial\Entity\Persona;
use App\Module\Residencial\Entity\Residencial;
use App\Module\Residencial\Repository\CasaRepository;
use App\Module\Residencial\Repository\IncidenteRepository;
use App\Module\Residencial\Repository\ManzanaRepository;
use App\Module\Residencial\Repository\PaqueteRepository;
use App\Module\Residencial\Repository\PersonaRepository;
use App\Module\Residencial\Repository\RegistroEntradaRepository;
use App\Module\Residencial\Repository\ResidencialRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESIDENCIAL_ADMIN')]
#[Route('/residencial', name: 'grova_residencial_admin_')]
final class ResidencialAdminController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly ResidencialRepository $residencialRepo,
        private readonly CasaRepository $casaRepo,
        private readonly ManzanaRepository $manzanaRepo,
        private readonly PersonaRepository $personaRepo,
        private readonly RegistroEntradaRepository $registroRepo,
        private readonly PaqueteRepository $paqueteRepo,
        private readonly IncidenteRepository $incidenteRepo,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepo,
    ) {
    }

    private function treeVars(string $activeKey = 'residencial-dashboard'): array
    {
        return [
            'menu_tree'               => $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER')),
            'active_menu_key'         => $activeKey,
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ];
    }

    private function tenantId(): int
    {
        return $this->tenantContext->getTenant()->getId();
    }

    // ── Dashboard ────────────────────────────────────────────────────────────

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $tenantId     = $this->tenantId();
        $residencial  = $this->residencialRepo->findPrimeroActivo($tenantId);

        return $this->render('workspace/pages/residencial/admin/dashboard.html.twig', array_merge($this->treeVars(), [
            'residencial'          => $residencial,
            'entradas_hoy'         => $residencial ? $this->registroRepo->countEntradasHoy($tenantId) : 0,
            'visitantes_activos'   => $residencial ? count($this->registroRepo->findVisitantesActivos($tenantId)) : 0,
            'paquetes_pendientes'  => $residencial ? $this->paqueteRepo->countPendientes($tenantId) : 0,
            'incidentes_mes'       => $residencial ? $this->incidenteRepo->countDelMes($tenantId) : 0,
        ]));
    }

    // ── Casas ────────────────────────────────────────────────────────────────

    #[Route('/casas', name: 'casas', methods: ['GET'])]
    public function casas(): Response
    {
        $tenantId    = $this->tenantId();
        $residencial = $this->residencialRepo->findPrimeroActivo($tenantId);

        return $this->render('workspace/pages/residencial/admin/casas.html.twig', array_merge($this->treeVars('residencial-casas'), [
            'residencial' => $residencial,
            'casas'       => $residencial ? $this->casaRepo->findByResidencial($residencial) : [],
            'manzanas'    => $residencial ? $this->manzanaRepo->findByResidencial($residencial) : [],
        ]));
    }

    #[Route('/casa/create', name: 'casa_create', methods: ['POST'])]
    public function createCasa(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_casa', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
        }

        $tenantId    = $this->tenantId();
        $residencial = $this->residencialRepo->findPrimeroActivo($tenantId);
        if ($residencial === null) {
            $this->addFlash('danger', 'No hay un residencial activo configurado.');
            return $this->redirectToRoute('grova_residencial_admin_dashboard', ['_locale' => $request->getLocale()]);
        }

        $casa = new Casa();
        $casa->setTenantId($tenantId);
        $casa->setResidencial($residencial);
        $this->fillCasaFromRequest($casa, $request);

        $em->persist($casa);
        $em->flush();

        $this->addFlash('success', sprintf('Casa %s creada.', $casa->getNumero()));
        return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
    }

    #[Route('/casa/{id}/edit', name: 'casa_edit', methods: ['POST'])]
    public function editCasa(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_casa_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
        }

        $casa = $this->casaRepo->find($id);
        if ($casa === null || $casa->getTenantId() !== $this->tenantId()) {
            throw $this->createNotFoundException();
        }

        $this->fillCasaFromRequest($casa, $request);
        $em->flush();

        $this->addFlash('success', 'Casa actualizada.');
        return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
    }

    #[Route('/casa/{id}/delete', name: 'casa_delete', methods: ['POST'])]
    public function deleteCasa(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_casa_del_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
        }

        $casa = $this->casaRepo->find($id);
        if ($casa === null || $casa->getTenantId() !== $this->tenantId()) {
            throw $this->createNotFoundException();
        }

        $em->remove($casa);
        $em->flush();

        $this->addFlash('success', 'Casa eliminada.');
        return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
    }

    private function fillCasaFromRequest(Casa $casa, Request $request): void
    {
        $casa->setNumero(trim((string) $request->request->get('numero', '')));
        $casa->setTipo((string) $request->request->get('tipo', 'casa'));
        $casa->setEstado((string) $request->request->get('estado', 'vacia'));

        $manzanaId = (int) $request->request->get('manzana_id', 0);
        if ($manzanaId > 0) {
            $manzana = $this->manzanaRepo->find($manzanaId);
            $casa->setManzana($manzana);
        } else {
            $casa->setManzana(null);
        }
    }

    // ── Residentes ───────────────────────────────────────────────────────────

    #[Route('/residentes', name: 'residentes', methods: ['GET'])]
    public function residentes(): Response
    {
        $tenantId = $this->tenantId();

        return $this->render('workspace/pages/residencial/admin/residentes.html.twig', array_merge($this->treeVars('residencial-residentes'), [
            'personas' => $this->personaRepo->findResidentesByTenant($tenantId),
            'casas'    => $this->casaRepo->findHabitadasByTenant($tenantId),
        ]));
    }

    #[Route('/residente/create', name: 'residente_create', methods: ['POST'])]
    public function createResidente(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_residente', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_residentes', ['_locale' => $request->getLocale()]);
        }

        $persona = new Persona();
        $persona->setTenantId($this->tenantId());
        $this->fillPersonaFromRequest($persona, $request);

        $em->persist($persona);
        $em->flush();

        $this->addFlash('success', sprintf('Residente "%s" agregado.', $persona->getNombreCompleto()));
        return $this->redirectToRoute('grova_residencial_admin_residentes', ['_locale' => $request->getLocale()]);
    }

    #[Route('/residente/{id}/edit', name: 'residente_edit', methods: ['POST'])]
    public function editResidente(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_residente_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_residentes', ['_locale' => $request->getLocale()]);
        }

        $persona = $this->personaRepo->find($id);
        if ($persona === null || $persona->getTenantId() !== $this->tenantId()) {
            throw $this->createNotFoundException();
        }

        $this->fillPersonaFromRequest($persona, $request);
        $em->flush();

        $this->addFlash('success', 'Residente actualizado.');
        return $this->redirectToRoute('grova_residencial_admin_residentes', ['_locale' => $request->getLocale()]);
    }

    private function fillPersonaFromRequest(Persona $persona, Request $request): void
    {
        $persona->setNombreCompleto(trim((string) $request->request->get('nombre_completo', '')));
        $persona->setDni(trim((string) $request->request->get('dni', '')) ?: null);
        $persona->setTelefono(trim((string) $request->request->get('telefono', '')) ?: null);
        $persona->setEmail(trim((string) $request->request->get('email', '')) ?: null);
        $persona->setTipo((string) $request->request->get('tipo', 'propietario'));
        $persona->setEstado((string) $request->request->get('estado', 'activo'));

        $casaId = (int) $request->request->get('casa_id', 0);
        if ($casaId > 0) {
            $casa = $this->casaRepo->find($casaId);
            $persona->setCasa($casa);
        } else {
            $persona->setCasa(null);
        }
    }

    // ── Historial ────────────────────────────────────────────────────────────

    #[Route('/historial', name: 'historial', methods: ['GET'])]
    public function historial(Request $request): Response
    {
        $tenantId = $this->tenantId();
        $desde    = new \DateTimeImmutable($request->query->get('desde', 'today'));
        $hasta    = new \DateTimeImmutable($request->query->get('hasta', 'today 23:59:59'));

        return $this->render('workspace/pages/residencial/admin/historial.html.twig', array_merge($this->treeVars('residencial-historial'), [
            'registros' => $this->registroRepo->findByRango($tenantId, $desde, $hasta),
            'desde'     => $desde,
            'hasta'     => $hasta,
        ]));
    }

    // ── Paquetes ─────────────────────────────────────────────────────────────

    #[Route('/paquetes', name: 'paquetes', methods: ['GET'])]
    public function paquetes(): Response
    {
        $tenantId = $this->tenantId();

        return $this->render('workspace/pages/residencial/admin/paquetes.html.twig', array_merge($this->treeVars('residencial-paquetes'), [
            'paquetes' => $this->paqueteRepo->findPendientesByTenant($tenantId),
        ]));
    }

    // ── Incidentes ───────────────────────────────────────────────────────────

    #[Route('/incidentes', name: 'incidentes', methods: ['GET'])]
    public function incidentes(): Response
    {
        $tenantId = $this->tenantId();

        return $this->render('workspace/pages/residencial/admin/incidentes.html.twig', array_merge($this->treeVars('residencial-incidentes'), [
            'incidentes' => $this->incidenteRepo->findByTenant($tenantId),
        ]));
    }

    // ── Mapa ─────────────────────────────────────────────────────────────────

    #[Route('/mapa', name: 'mapa', methods: ['GET'])]
    public function mapaEditor(): Response
    {
        $tenantId    = $this->tenantId();
        $residencial = $this->residencialRepo->findPrimeroActivo($tenantId);

        return $this->render('workspace/pages/residencial/admin/mapa_editor.html.twig', array_merge($this->treeVars('residencial-mapa'), [
            'residencial' => $residencial,
            'casas'       => $residencial ? $this->casaRepo->findByResidencial($residencial) : [],
            'manzanas'    => $residencial ? $this->manzanaRepo->findByResidencial($residencial) : [],
        ]));
    }

    #[Route('/mapa/guardar', name: 'mapa_guardar', methods: ['POST'])]
    public function mapaGuardar(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_mapa', (string) $request->request->get('_token'))) {
            return $this->json(['ok' => false, 'error' => 'Token inválido'], 400);
        }

        $tenantId    = $this->tenantId();
        $residencial = $this->residencialRepo->findPrimeroActivo($tenantId);
        if ($residencial === null) {
            return $this->json(['ok' => false, 'error' => 'Residencial no encontrado'], 404);
        }

        $mapaJson = json_decode((string) $request->request->get('mapa_json', '{}'), true);
        $residencial->setMapaJson($mapaJson);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    // ── Manzanas ─────────────────────────────────────────────────────────────

    #[Route('/manzana/create', name: 'manzana_create', methods: ['POST'])]
    public function createManzana(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_manzana', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
        }

        $tenantId    = $this->tenantId();
        $residencial = $this->residencialRepo->findPrimeroActivo($tenantId);
        if ($residencial === null) {
            $this->addFlash('danger', 'No hay un residencial activo configurado.');
            return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
        }

        $manzana = new Manzana();
        $manzana->setTenantId($tenantId);
        $manzana->setResidencial($residencial);
        $manzana->setNombre(trim((string) $request->request->get('nombre', '')));

        $em->persist($manzana);
        $em->flush();

        $this->addFlash('success', sprintf('Manzana "%s" creada.', $manzana->getNombre()));
        return $this->redirectToRoute('grova_residencial_admin_casas', ['_locale' => $request->getLocale()]);
    }

    // ── Configuración del residencial ────────────────────────────────────────

    #[Route('/configurar', name: 'configurar', methods: ['POST'])]
    public function configurar(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_config', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_dashboard', ['_locale' => $request->getLocale()]);
        }

        $tenantId    = $this->tenantId();
        $residencial = $this->residencialRepo->findPrimeroActivo($tenantId);

        if ($residencial === null) {
            $residencial = new Residencial();
            $residencial->setTenantId($tenantId);
            $em->persist($residencial);
        }

        $residencial->setNombre(trim((string) $request->request->get('nombre', '')));
        $residencial->setDireccion(trim((string) $request->request->get('direccion', '')) ?: null);
        $residencial->setTelefono(trim((string) $request->request->get('telefono', '')) ?: null);

        $lat = trim((string) $request->request->get('coordenadas_lat', ''));
        $lng = trim((string) $request->request->get('coordenadas_lng', ''));
        $residencial->setCoordenadasLat($lat !== '' ? $lat : null);
        $residencial->setCoordenadasLng($lng !== '' ? $lng : null);

        $em->flush();

        $this->addFlash('success', 'Residencial configurado.');
        return $this->redirectToRoute('grova_residencial_admin_dashboard', ['_locale' => $request->getLocale()]);
    }

    // ── Usuarios del residencial ─────────────────────────────────────────────

    #[Route('/usuarios', name: 'usuarios', methods: ['GET'])]
    public function usuarios(): Response
    {
        $tenantId = $this->tenantId();
        $tenant   = $this->tenantContext->getTenant();

        return $this->render('workspace/pages/residencial/admin/usuarios.html.twig', array_merge($this->treeVars('residencial-dashboard'), [
            'usuarios' => $this->userRepo->findByRolesResidencial($tenant),
            'personas' => $this->personaRepo->findResidentesByTenant($tenantId),
            'casas'    => $this->casaRepo->findHabitadasByTenant($tenantId),
        ]));
    }

    #[Route('/usuarios/crear', name: 'usuario_crear', methods: ['POST'])]
    public function usuarioCrear(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_usuario', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_usuarios', ['_locale' => $request->getLocale()]);
        }

        $nombre    = trim((string) $request->request->get('nombre', ''));
        $email     = trim((string) $request->request->get('email', ''));
        $password  = trim((string) $request->request->get('password', ''));
        $rol       = (string) $request->request->get('rol', 'ROLE_RESIDENCIAL_GUARDIA');
        $personaId = (int) $request->request->get('persona_id', 0);

        if ($nombre === '' || $email === '' || $password === '') {
            $this->addFlash('danger', 'Nombre, email y contraseña son obligatorios.');
            return $this->redirectToRoute('grova_residencial_admin_usuarios', ['_locale' => $request->getLocale()]);
        }

        $rolesPermitidos = ['ROLE_RESIDENCIAL_GUARDIA', 'ROLE_RESIDENCIAL_RESIDENTE'];
        if (!in_array($rol, $rolesPermitidos, true)) {
            $this->addFlash('danger', 'Rol no válido.');
            return $this->redirectToRoute('grova_residencial_admin_usuarios', ['_locale' => $request->getLocale()]);
        }

        $usuario = new User();
        $usuario->setNombre($nombre);
        $usuario->setEmail($email);
        $usuario->setUsername(strtolower(str_replace([' ', '@'], ['_', '_at_'], $email)));
        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $password));
        $usuario->setRoles([$rol]);
        $usuario->setEmailVerificado(true);
        $usuario->setTenant($this->tenantContext->getTenant());

        $em->persist($usuario);
        $em->flush();

        // Si es residente, vincular a su Persona
        if ($rol === 'ROLE_RESIDENCIAL_RESIDENTE' && $personaId > 0) {
            $persona = $this->personaRepo->find($personaId);
            if ($persona !== null && $persona->getTenantId() === $this->tenantId()) {
                $persona->setUserId($usuario->getId());
                $em->flush();
            }
        }

        $this->addFlash('success', sprintf('Usuario "%s" creado como %s.', $nombre, $rol === 'ROLE_RESIDENCIAL_GUARDIA' ? 'Guardia' : 'Residente'));
        return $this->redirectToRoute('grova_residencial_admin_usuarios', ['_locale' => $request->getLocale()]);
    }

    #[Route('/usuarios/{id}/eliminar', name: 'usuario_eliminar', methods: ['POST'])]
    public function usuarioEliminar(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_usuario_del_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_admin_usuarios', ['_locale' => $request->getLocale()]);
        }

        $usuario = $this->userRepo->find($id);
        if ($usuario === null || $usuario->getTenant() !== $this->tenantContext->getTenant()) {
            throw $this->createNotFoundException();
        }

        // Desvincular persona si existe
        $persona = $this->personaRepo->findByUserId($id);
        if ($persona !== null) {
            $persona->setUserId(null);
        }

        $em->remove($usuario);
        $em->flush();

        $this->addFlash('success', 'Usuario eliminado.');
        return $this->redirectToRoute('grova_residencial_admin_usuarios', ['_locale' => $request->getLocale()]);
    }
}
