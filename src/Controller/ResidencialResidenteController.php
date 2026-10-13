<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Residencial\Entity\Pase;
use App\Module\Residencial\Entity\Visitante;
use App\Module\Residencial\Repository\CasaRepository;
use App\Module\Residencial\Repository\PaseRepository;
use App\Module\Residencial\Repository\PersonaRepository;
use App\Module\Residencial\Repository\VisitanteRepository;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESIDENCIAL_RESIDENTE')]
#[Route('/residencial/residente', name: 'grova_residencial_residente_')]
final class ResidencialResidenteController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly PersonaRepository $personaRepo,
        private readonly CasaRepository $casaRepo,
        private readonly PaseRepository $paseRepo,
        private readonly VisitanteRepository $visitanteRepo,
    ) {
    }

    private function arbolMenu(string $claveActiva = 'residencial-mis-invitaciones'): array
    {
        return [
            'menu_tree'               => $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER')),
            'active_menu_key'         => $claveActiva,
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ];
    }

    private function tenantId(): int
    {
        return $this->tenantContext->getTenant()->getId();
    }

    // ── Mis invitaciones ─────────────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $tenantId  = $this->tenantId();
        $usuario   = $this->getUser();
        $residente = $this->personaRepo->findResidenteByUser($usuario->getId(), $tenantId);

        if ($residente === null) {
            return $this->render('workspace/pages/residencial/residente/sin_perfil.html.twig', $this->arbolMenu());
        }

        $pases = $this->paseRepo->findByResidente($residente);

        return $this->render('workspace/pages/residencial/residente/mis_invitaciones.html.twig', array_merge($this->arbolMenu(), [
            'residente' => $residente,
            'pases'     => $pases,
        ]));
    }

    // ── Crear invitación ─────────────────────────────────────────────────────

    #[Route('/crear-invitacion', name: 'crear_invitacion', methods: ['GET'])]
    public function crearInvitacionForm(): Response
    {
        $tenantId  = $this->tenantId();
        $usuario   = $this->getUser();
        $residente = $this->personaRepo->findResidenteByUser($usuario->getId(), $tenantId);

        if ($residente === null) {
            return $this->redirectToRoute('grova_residencial_residente_index', ['_locale' => 'es']);
        }

        return $this->render('workspace/pages/residencial/residente/crear_invitacion.html.twig', array_merge($this->arbolMenu(), [
            'residente' => $residente,
            'casa'      => $residente->getCasa(),
        ]));
    }

    #[Route('/crear-invitacion', name: 'crear_invitacion_submit', methods: ['POST'])]
    public function crearInvitacionSubmit(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_pase', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_residente_crear_invitacion', ['_locale' => $request->getLocale()]);
        }

        $tenantId  = $this->tenantId();
        $usuario   = $this->getUser();
        $residente = $this->personaRepo->findResidenteByUser($usuario->getId(), $tenantId);

        if ($residente === null || $residente->getCasa() === null) {
            $this->addFlash('danger', 'Tu perfil no está vinculado a una casa.');
            return $this->redirectToRoute('grova_residencial_residente_index', ['_locale' => $request->getLocale()]);
        }

        // Buscar visitante por DNI o crear uno nuevo
        $dniVisitante = trim((string) $request->request->get('visitante_dni', ''));
        $visitante    = $dniVisitante !== '' ? $this->visitanteRepo->findByDni($tenantId, $dniVisitante) : null;

        if ($visitante === null) {
            $visitante = new Visitante();
            $visitante->setTenantId($tenantId);
            $visitante->setNombreCompleto(trim((string) $request->request->get('visitante_nombre', '')));
            $visitante->setDni($dniVisitante ?: null);
            $visitante->setTelefono(trim((string) $request->request->get('visitante_telefono', '')) ?: null);
            $em->persist($visitante);
        }

        $pase = new Pase();
        $pase->setTenantId($tenantId);
        $pase->setResidente($residente);
        $pase->setVisitante($visitante);
        $pase->setCasaDestino($residente->getCasa());
        $pase->setTipo((string) $request->request->get('tipo', 'unica_vez'));

        $fechaInicio = trim((string) $request->request->get('fecha_inicio', ''));
        $fechaFin    = trim((string) $request->request->get('fecha_fin', ''));
        $pase->setFechaInicio($fechaInicio !== '' ? new \DateTimeImmutable($fechaInicio) : null);
        $pase->setFechaFin($fechaFin !== '' ? new \DateTimeImmutable($fechaFin) : null);

        $horaInicio = trim((string) $request->request->get('hora_inicio', ''));
        $horaFin    = trim((string) $request->request->get('hora_fin', ''));
        $pase->setHoraInicio($horaInicio ?: null);
        $pase->setHoraFin($horaFin ?: null);

        $diasSemana = $request->request->all('dias_semana');
        $pase->setDiasSemana(!empty($diasSemana) ? array_map('intval', $diasSemana) : null);

        $em->persist($pase);
        $em->flush();

        $this->addFlash('success', sprintf('Invitación creada para %s.', $visitante->getNombreCompleto()));
        return $this->redirectToRoute('grova_residencial_residente_ver_pase', [
            'id'      => $pase->getId(),
            '_locale' => $request->getLocale(),
        ]);
    }

    // ── Ver QR de un pase ────────────────────────────────────────────────────

    #[Route('/pase/{id}/qr', name: 'ver_pase', methods: ['GET'])]
    public function verPase(int $id): Response
    {
        $tenantId  = $this->tenantId();
        $pase      = $this->paseRepo->find($id);

        if ($pase === null || $pase->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }

        return $this->render('workspace/pages/residencial/residente/ver_pase.html.twig', array_merge($this->arbolMenu(), [
            'pase' => $pase,
        ]));
    }

    // ── Cancelar un pase ─────────────────────────────────────────────────────

    #[Route('/pase/{id}/cancelar', name: 'cancelar_pase', methods: ['POST'])]
    public function cancelarPase(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('residencial_cancelar_pase_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_residencial_residente_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantId();
        $pase     = $this->paseRepo->find($id);

        if ($pase === null || $pase->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }

        $pase->setEstado('cancelado');
        $em->flush();

        $this->addFlash('success', 'Invitación cancelada.');
        return $this->redirectToRoute('grova_residencial_residente_index', ['_locale' => $request->getLocale()]);
    }

    // ── Historial de visitas ─────────────────────────────────────────────────

    #[Route('/historial', name: 'historial', methods: ['GET'])]
    public function historial(): Response
    {
        $tenantId  = $this->tenantId();
        $usuario   = $this->getUser();
        $residente = $this->personaRepo->findResidenteByUser($usuario->getId(), $tenantId);

        $pases = $residente ? $this->paseRepo->findByResidente($residente) : [];

        return $this->render('workspace/pages/residencial/residente/historial.html.twig', array_merge($this->arbolMenu('residencial-historial-visitas'), [
            'residente' => $residente,
            'pases'     => $pases,
        ]));
    }
}
