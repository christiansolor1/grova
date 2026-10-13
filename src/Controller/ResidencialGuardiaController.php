<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Residencial\Entity\Incidente;
use App\Module\Residencial\Entity\Paquete;
use App\Module\Residencial\Entity\RegistroEntrada;
use App\Module\Residencial\Entity\Visitante;
use App\Module\Residencial\Repository\CasaRepository;
use App\Module\Residencial\Repository\PaqueteRepository;
use App\Module\Residencial\Repository\PersonaRepository;
use App\Module\Residencial\Repository\RegistroEntradaRepository;
use App\Module\Residencial\Repository\ResidencialRepository;
use App\Module\Residencial\Repository\VisitanteRepository;
use App\Module\Residencial\Service\ResidencialPaseValidatorService;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESIDENCIAL_GUARDIA')]
#[Route('/residencial/garita', name: 'grova_residencial_guardia_')]
final class ResidencialGuardiaController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly ResidencialRepository $residencialRepo,
        private readonly CasaRepository $casaRepo,
        private readonly PersonaRepository $personaRepo,
        private readonly VisitanteRepository $visitanteRepo,
        private readonly RegistroEntradaRepository $registroRepo,
        private readonly PaqueteRepository $paqueteRepo,
        private readonly ResidencialPaseValidatorService $validadorPase,
    ) {
    }

    private function arbolMenu(): array
    {
        return [
            'menu_tree'               => $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER')),
            'active_menu_key'         => 'residencial-garita',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ];
    }

    private function tenantId(): int
    {
        return $this->tenantContext->getTenant()->getId();
    }

    // ── Panel principal de garita ────────────────────────────────────────────

    #[Route('', name: 'garita', methods: ['GET'])]
    public function garita(): Response
    {
        $tenantId         = $this->tenantId();
        $visitantesActivos = $this->registroRepo->findVisitantesActivos($tenantId);
        $ultimasEntradas  = $this->registroRepo->findUltimasEntradas($tenantId, 10);
        $residencial      = $this->residencialRepo->findPrimeroActivo($tenantId);

        return $this->render('workspace/pages/residencial/guardia/garita.html.twig', array_merge($this->arbolMenu(), [
            'residencial'       => $residencial,
            'visitantes_activos' => $visitantesActivos,
            'ultimas_entradas'  => $ultimasEntradas,
        ]));
    }

    // ── Escaneo de QR (responde JSON) ────────────────────────────────────────

    #[Route('/escanear-qr', name: 'escanear_qr', methods: ['POST'])]
    public function escanearQr(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('residencial_garita_qr', (string) $request->request->get('_token'))) {
            return $this->json(['ok' => false, 'razon' => 'Token inválido'], 400);
        }

        $token     = trim((string) $request->request->get('token', ''));
        $tenantId  = $this->tenantId();
        $resultado = $this->validadorPase->validar($token, $tenantId);

        return $this->json($resultado->toArray());
    }

    // ── Registrar entrada tras validación QR ─────────────────────────────────

    #[Route('/entrada/registrar', name: 'entrada_registrar', methods: ['POST'])]
    public function registrarEntrada(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_entrada', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
        }

        $tenantId  = $this->tenantId();
        $paseId    = (int) $request->request->get('pase_id', 0);
        $visitanteId = (int) $request->request->get('visitante_id', 0);
        $guardiaId = (int) $request->request->get('guardia_id', 0);

        $visitante = $this->visitanteRepo->find($visitanteId);
        $guardia   = $this->personaRepo->find($guardiaId);

        if ($visitante === null || $guardia === null) {
            $this->addFlash('danger', 'Datos incompletos para registrar la entrada.');
            return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
        }

        $registro = new RegistroEntrada();
        $registro->setTenantId($tenantId);
        $registro->setVisitante($visitante);
        $registro->setGuardia($guardia);
        $registro->setTipoEntrada('qr');

        if ($paseId > 0) {
            // Si había pase válido lo vinculamos
        }

        $em->persist($registro);
        $em->flush();

        $this->addFlash('success', sprintf('Entrada de %s registrada.', $visitante->getNombreCompleto()));
        return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
    }

    // ── Registrar salida ─────────────────────────────────────────────────────

    #[Route('/entrada/{id}/salida', name: 'registrar_salida', methods: ['POST'])]
    public function registrarSalida(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_salida_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
        }

        $registro = $this->registroRepo->find($id);
        if ($registro === null || $registro->getTenantId() !== $this->tenantId()) {
            throw $this->createNotFoundException();
        }

        $registro->setHoraSalida(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', sprintf('Salida de %s registrada.', $registro->getVisitante()->getNombreCompleto()));
        return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
    }

    // ── Entrada manual (sin QR) ──────────────────────────────────────────────

    #[Route('/entrada-manual', name: 'entrada_manual', methods: ['GET', 'POST'])]
    public function entradaManual(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('GET')) {
            $tenantId = $this->tenantId();
            return $this->render('workspace/pages/residencial/guardia/entrada_manual.html.twig', array_merge($this->arbolMenu(), [
                'casas'    => $this->casaRepo->findHabitadasByTenant($tenantId),
                'guardias' => $this->personaRepo->findGuardiasActivos($tenantId),
            ]));
        }

        if (!$this->isCsrfTokenValid('residencial_entrada_manual', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_guardia_entrada_manual', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantId();
        $dni      = trim((string) $request->request->get('dni', ''));

        // Buscar visitante existente por DNI o crear uno nuevo
        $visitante = $dni !== '' ? $this->visitanteRepo->findByDni($tenantId, $dni) : null;
        $esPrimera = $visitante === null;

        if ($visitante === null) {
            $visitante = new Visitante();
            $visitante->setTenantId($tenantId);
            $visitante->setNombreCompleto(trim((string) $request->request->get('nombre_completo', '')));
            $visitante->setDni($dni ?: null);
            $visitante->setTelefono(trim((string) $request->request->get('telefono', '')) ?: null);
            $em->persist($visitante);
        }

        $guardiaId = (int) $request->request->get('guardia_id', 0);
        $guardia   = $this->personaRepo->find($guardiaId);

        if ($guardia === null) {
            $this->addFlash('danger', 'Guardia no encontrado.');
            return $this->redirectToRoute('grova_residencial_guardia_entrada_manual', ['_locale' => $request->getLocale()]);
        }

        $registro = new RegistroEntrada();
        $registro->setTenantId($tenantId);
        $registro->setVisitante($visitante);
        $registro->setGuardia($guardia);
        $registro->setTipoEntrada('manual');
        $registro->setEsPrimeraVez($esPrimera);
        $registro->setObservaciones(trim((string) $request->request->get('observaciones', '')) ?: null);

        $em->persist($registro);
        $em->flush();

        $this->addFlash('success', sprintf('Entrada manual de %s registrada.', $visitante->getNombreCompleto()));
        return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
    }

    // ── Búsqueda de visitante ────────────────────────────────────────────────

    #[Route('/buscar-visitante', name: 'buscar_visitante', methods: ['GET'])]
    public function buscarVisitante(Request $request): Response
    {
        $termino   = trim((string) $request->query->get('q', ''));
        $tenantId  = $this->tenantId();
        $visitantes = $termino !== '' ? $this->visitanteRepo->buscar($tenantId, $termino) : [];

        if ($request->isXmlHttpRequest()) {
            return $this->json(array_map(fn($v) => [
                'id'     => $v->getId(),
                'nombre' => $v->getNombreCompleto(),
                'dni'    => $v->getDni(),
            ], $visitantes));
        }

        return $this->render('workspace/pages/residencial/guardia/buscar_visitante.html.twig', array_merge($this->arbolMenu(), [
            'visitantes' => $visitantes,
            'termino'    => $termino,
        ]));
    }

    // ── Paquetes en garita ───────────────────────────────────────────────────

    #[Route('/paquetes', name: 'paquetes', methods: ['GET'])]
    public function paquetes(): Response
    {
        $tenantId = $this->tenantId();

        return $this->render('workspace/pages/residencial/guardia/paquetes.html.twig', array_merge($this->arbolMenu(), [
            'paquetes' => $this->paqueteRepo->findPendientesByTenant($tenantId),
            'casas'    => $this->casaRepo->findHabitadasByTenant($tenantId),
            'personas' => $this->personaRepo->findResidentesByTenant($tenantId),
        ]));
    }

    #[Route('/paquete/recibir', name: 'paquete_recibir', methods: ['POST'])]
    public function recibirPaquete(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_paquete', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_guardia_paquetes', ['_locale' => $request->getLocale()]);
        }

        $tenantId  = $this->tenantId();
        $residenteId = (int) $request->request->get('residente_id', 0);
        $casaId    = (int) $request->request->get('casa_id', 0);

        $residente = $this->personaRepo->find($residenteId);
        $casa      = $this->casaRepo->find($casaId);

        if ($residente === null || $casa === null) {
            $this->addFlash('danger', 'Residente o casa no encontrados.');
            return $this->redirectToRoute('grova_residencial_guardia_paquetes', ['_locale' => $request->getLocale()]);
        }

        $paquete = new Paquete();
        $paquete->setTenantId($tenantId);
        $paquete->setResidente($residente);
        $paquete->setCasa($casa);
        $paquete->setEmpresaMensajeria(trim((string) $request->request->get('empresa_mensajeria', '')) ?: null);
        $paquete->setDescripcion(trim((string) $request->request->get('descripcion', '')) ?: null);
        $paquete->setEstado('recibido');

        $em->persist($paquete);
        $em->flush();

        $this->addFlash('success', 'Paquete registrado.');
        return $this->redirectToRoute('grova_residencial_guardia_paquetes', ['_locale' => $request->getLocale()]);
    }

    // ── Incidentes ───────────────────────────────────────────────────────────

    #[Route('/incidente/crear', name: 'incidente_crear', methods: ['POST'])]
    public function crearIncidente(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_incidente', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
        }

        $tenantId  = $this->tenantId();
        $guardiaId = (int) $request->request->get('guardia_id', 0);
        $guardia   = $this->personaRepo->find($guardiaId);

        if ($guardia === null) {
            $this->addFlash('danger', 'Guardia no encontrado.');
            return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
        }

        $incidente = new Incidente();
        $incidente->setTenantId($tenantId);
        $incidente->setGuardia($guardia);
        $incidente->setTipo((string) $request->request->get('tipo', 'otro'));
        $incidente->setDescripcion(trim((string) $request->request->get('descripcion', '')));

        $em->persist($incidente);
        $em->flush();

        $this->addFlash('success', 'Incidente registrado.');
        return $this->redirectToRoute('grova_residencial_guardia_garita', ['_locale' => $request->getLocale()]);
    }

    // ── Mapa (vista guardia, solo lectura) ───────────────────────────────────

    #[Route('/mapa', name: 'mapa', methods: ['GET'])]
    public function mapa(Request $request): Response
    {
        $tenantId    = $this->tenantId();
        $residencial = $this->residencialRepo->findPrimeroActivo($tenantId);
        $casaId      = (int) $request->query->get('casa_id', 0);

        return $this->render('workspace/pages/residencial/guardia/mapa.html.twig', array_merge($this->arbolMenu(), [
            'residencial'     => $residencial,
            'casas'           => $residencial ? $this->casaRepo->findByResidencial($residencial) : [],
            'casa_resaltada'  => $casaId > 0 ? $this->casaRepo->find($casaId) : null,
        ]));
    }
}
