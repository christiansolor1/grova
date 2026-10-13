<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Agenda\Entity\AgendaEvento;
use App\Module\Personal\Agenda\Repository\AgendaEventoRepository;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/agenda', name: 'grova_agenda_')]
final class AgendaController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly AgendaEventoRepository $eventoRepo,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $tenantId = $this->tenantContext->getTenant()->getId();
        $tree     = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        $proximos = $this->eventoRepo->findProximos($tenantId, 60);
        $todos    = $this->eventoRepo->findByTenant($tenantId);

        $porMes = [];
        foreach ($todos as $evento) {
            $mes        = (int) $evento->getFecha()->format('n');
            $anio       = (int) $evento->getFecha()->format('Y');
            $clave      = sprintf('%04d-%02d', $anio, $mes);
            $porMes[$clave][] = $evento;
        }
        ksort($porMes);

        return $this->render('workspace/pages/agenda/index.html.twig', [
            'menu_tree' => $tree,
            'proximos'  => $proximos,
            'porMes'    => $porMes,
            'todos'     => $todos,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createEvento(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('agenda_evento', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('agenda.flash_token_invalid', [], 'agenda'));
            return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();

        $fechaStr = trim((string) $request->request->get('fecha', date('Y-m-d')));
        try {
            $fecha = new \DateTimeImmutable($fechaStr);
        } catch (\Exception) {
            $fecha = new \DateTimeImmutable();
        }

        $nombre           = trim((string) $request->request->get('nombre', ''));
        $apodo            = trim((string) $request->request->get('apodo', ''));
        $tipo             = trim((string) $request->request->get('tipo', 'evento'));
        $parentesco       = trim((string) $request->request->get('parentesco', ''));
        $recurrente       = (bool) $request->request->get('recurrente', false);
        $hora             = trim((string) $request->request->get('hora', ''));
        $lugar            = trim((string) $request->request->get('lugar', ''));
        $url              = trim((string) $request->request->get('url', ''));
        $notas            = trim((string) $request->request->get('notas', ''));
        $color            = trim((string) $request->request->get('color', ''));
        $notificarCorreo = (bool) $request->request->get('notificar_correo', false);
        $notificarPush   = (bool) $request->request->get('notificar_push', false);
        $avisos          = $this->leerAvisos($request);

        if ($nombre === '') {
            $this->addFlash('danger', $this->translator->trans('agenda.flash_nombre_requerido', [], 'agenda'));
            return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
        }

        $tipoValido = in_array($tipo, ['cumpleanios', 'evento', 'recordatorio'], true) ? $tipo : 'evento';

        $evento = new AgendaEvento();
        $evento->setTenantId($tenantId);
        $evento->setNombre($nombre);
        $evento->setApodo($apodo);
        $evento->setFecha($fecha);
        $evento->setTipo($tipoValido);
        $evento->setParentesco($tipoValido === 'cumpleanios' ? $parentesco : null);
        $evento->setRecurrente($recurrente);
        $evento->setHora($tipoValido !== 'cumpleanios' ? $hora : null);
        $evento->setLugar($tipoValido !== 'cumpleanios' ? $lugar : null);
        $evento->setUrl($tipoValido !== 'cumpleanios' ? $url : null);
        $evento->setNotas($notas !== '' ? $notas : null);
        $evento->setColor($color !== '' ? $color : null);
        $evento->setNotificarCorreo($notificarCorreo);
        $evento->setNotificarPush($notificarPush);
        $evento->setAvisos($avisos);

        $em->persist($evento);
        $em->flush();

        $this->addFlash('success', $this->translator->trans('agenda.flash_evento_creado', [], 'agenda'));

        return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    public function editEvento(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('agenda_evento_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('agenda.flash_token_invalid', [], 'agenda'));
            return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $evento   = $this->eventoRepo->find($id);

        if ($evento === null || $evento->getTenantId() !== $tenantId) {
            throw $this->createNotFoundException();
        }

        $fechaStr = trim((string) $request->request->get('fecha', $evento->getFecha()->format('Y-m-d')));
        try {
            $fecha = new \DateTimeImmutable($fechaStr);
        } catch (\Exception) {
            $fecha = $evento->getFecha();
        }

        $nombre           = trim((string) $request->request->get('nombre', ''));
        $apodo            = trim((string) $request->request->get('apodo', ''));
        $tipo             = trim((string) $request->request->get('tipo', 'evento'));
        $parentesco       = trim((string) $request->request->get('parentesco', ''));
        $recurrente       = (bool) $request->request->get('recurrente', false);
        $hora             = trim((string) $request->request->get('hora', ''));
        $lugar            = trim((string) $request->request->get('lugar', ''));
        $url              = trim((string) $request->request->get('url', ''));
        $notas            = trim((string) $request->request->get('notas', ''));
        $color            = trim((string) $request->request->get('color', ''));
        $notificarCorreo = (bool) $request->request->get('notificar_correo', false);
        $notificarPush   = (bool) $request->request->get('notificar_push', false);
        $avisos          = $this->leerAvisos($request);

        if ($nombre === '') {
            $this->addFlash('danger', $this->translator->trans('agenda.flash_nombre_requerido', [], 'agenda'));
            return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
        }

        $tipoValido = in_array($tipo, ['cumpleanios', 'evento', 'recordatorio'], true) ? $tipo : 'evento';

        $evento->setNombre($nombre);
        $evento->setApodo($apodo);
        $evento->setFecha($fecha);
        $evento->setTipo($tipoValido);
        $evento->setParentesco($tipoValido === 'cumpleanios' ? $parentesco : null);
        $evento->setRecurrente($recurrente);
        $evento->setHora($tipoValido !== 'cumpleanios' ? $hora : null);
        $evento->setLugar($tipoValido !== 'cumpleanios' ? $lugar : null);
        $evento->setUrl($tipoValido !== 'cumpleanios' ? $url : null);
        $evento->setNotas($notas !== '' ? $notas : null);
        $evento->setColor($color !== '' ? $color : null);
        $evento->setNotificarCorreo($notificarCorreo);
        $evento->setNotificarPush($notificarPush);
        $evento->setAvisos($avisos);

        $em->flush();

        $this->addFlash('success', $this->translator->trans('agenda.flash_evento_actualizado', [], 'agenda'));

        return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function deleteEvento(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('agenda_evento_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('agenda.flash_token_invalid', [], 'agenda'));
            return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $evento   = $this->eventoRepo->find($id);

        if ($evento !== null && $evento->getTenantId() === $tenantId) {
            $em->remove($evento);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('agenda.flash_evento_eliminado', [], 'agenda'));
        }

        return $this->redirectToRoute('grova_agenda_index', ['_locale' => $request->getLocale()]);
    }

    /** @return list<int> */
    private function leerAvisos(Request $request): array
    {
        $slots = [
            (int) $request->request->get('aviso_1', -1),
            (int) $request->request->get('aviso_2', -1),
            (int) $request->request->get('aviso_3', -1),
        ];
        return array_values(array_unique(array_filter($slots, fn($v) => $v >= 0)));
    }
}
