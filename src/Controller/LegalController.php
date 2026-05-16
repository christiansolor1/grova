<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Core\Contact\Repository\ContactRepository;
use App\Module\Legal\Entity\LegalCase;
use App\Module\Legal\Entity\LegalDocument;
use App\Module\Legal\Entity\LegalFollowUp;
use App\Module\Legal\Entity\LegalPayment;
use App\Module\Legal\Repository\LegalCaseRepository;
use App\Module\Legal\Repository\LegalFollowUpRepository;
use App\Service\MenuTreeBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/legal', name: 'grova_legal_')]
final class LegalController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly LegalCaseRepository $caseRepo,
        private readonly LegalFollowUpRepository $followUpRepo,
        private readonly ContactRepository $contactRepo,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $tree      = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $cases     = $this->caseRepo->findAllWithContact();
        $stats     = $this->caseRepo->countByEstado();
        $audiencias = $this->followUpRepo->findProximasAudiencias();
        $pendiente = $this->caseRepo->getTotalHonorariosPendientes();

        return $this->render('workspace/pages/legal/index.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'legal',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'cases'                   => $cases,
            'stats'                   => $stats,
            'audiencias'              => $audiencias,
            'pendiente'               => $pendiente,
        ]);
    }

    #[Route('/case/create', name: 'case_create', methods: ['POST'])]
    public function createCase(
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('legal_case', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_legal_index', ['_locale' => $request->getLocale()]);
        }

        $contact = $this->contactRepo->find((int) $request->request->get('contact_id'));
        if ($contact === null) {
            $this->addFlash('danger', 'Contacto no encontrado.');
            return $this->redirectToRoute('grova_legal_index', ['_locale' => $request->getLocale()]);
        }

        $case = new LegalCase();
        $case->setContact($contact);
        $case->setNumero(trim((string) $request->request->get('numero', '')) ?: null);
        $case->setTipo((string) $request->request->get('tipo', 'civil'));
        $case->setEstado('abierto');
        $case->setTitulo(trim((string) $request->request->get('titulo', '')));
        $case->setDescripcion(trim((string) $request->request->get('descripcion', '')) ?: null);
        $case->setFechaApertura(new \DateTimeImmutable((string) $request->request->get('fecha_apertura', date('Y-m-d'))));
        $honorarios = $request->request->get('honorarios');
        $case->setHonorarios($honorarios ? number_format(abs((float) $honorarios), 2, '.', '') : null);

        $em->persist($case);
        $em->flush();

        $this->addFlash('success', sprintf('Caso "%s" creado.', $case->getTitulo()));
        return $this->redirectToRoute('grova_legal_case_show', ['id' => $case->getId(), '_locale' => $request->getLocale()]);
    }

    #[Route('/case/{id}', name: 'case_show', methods: ['GET'])]
    public function showCase(int $id): Response
    {
        $case = $this->caseRepo->find($id);
        if ($case === null) { throw $this->createNotFoundException(); }

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('workspace/pages/legal/case.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'legal',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'case'                    => $case,
        ]);
    }

    #[Route('/case/{id}/edit', name: 'case_edit', methods: ['POST'])]
    public function editCase(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('legal_case_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $case = $this->caseRepo->find($id);
        if ($case === null) { throw $this->createNotFoundException(); }

        $case->setNumero(trim((string) $request->request->get('numero', '')) ?: null);
        $case->setTipo((string) $request->request->get('tipo', 'civil'));
        $case->setEstado((string) $request->request->get('estado', 'abierto'));
        $case->setTitulo(trim((string) $request->request->get('titulo', '')));
        $case->setDescripcion(trim((string) $request->request->get('descripcion', '')) ?: null);
        $honorarios = $request->request->get('honorarios');
        $case->setHonorarios($honorarios ? number_format(abs((float) $honorarios), 2, '.', '') : null);

        $em->flush();
        $this->addFlash('success', 'Caso actualizado.');
        return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }

    #[Route('/case/{id}/followup/add', name: 'followup_add', methods: ['POST'])]
    public function addFollowUp(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('legal_followup_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $case = $this->caseRepo->find($id);
        if ($case === null) { throw $this->createNotFoundException(); }

        $followUp = new LegalFollowUp();
        $followUp->setCase($case);
        $followUp->setDescripcion(trim((string) $request->request->get('descripcion', '')));
        $followUp->setFecha(new \DateTimeImmutable((string) $request->request->get('fecha', date('Y-m-d'))));

        $audienciaStr = trim((string) $request->request->get('proxima_audiencia', ''));
        $followUp->setProximaAudiencia($audienciaStr !== '' ? new \DateTimeImmutable($audienciaStr) : null);

        $em->persist($followUp);
        $em->flush();

        $this->addFlash('success', 'Seguimiento agregado.');
        return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }

    #[Route('/case/{id}/payment/add', name: 'payment_add', methods: ['POST'])]
    public function addPayment(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('legal_payment_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $case = $this->caseRepo->find($id);
        if ($case === null) { throw $this->createNotFoundException(); }

        $payment = new LegalPayment();
        $payment->setCase($case);
        $payment->setConcepto(trim((string) $request->request->get('concepto', '')));
        $payment->setMonto(abs((float) $request->request->get('monto', 0)));
        $payment->setEstado((string) $request->request->get('estado', 'pendiente'));

        $fechaPago = trim((string) $request->request->get('fecha_pago', ''));
        $payment->setFechaPago($fechaPago !== '' ? new \DateTimeImmutable($fechaPago) : null);

        $em->persist($payment);
        $em->flush();

        $this->addFlash('success', 'Cobro registrado.');
        return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }

    #[Route('/case/{id}/document/upload', name: 'document_upload', methods: ['POST'])]
    public function uploadDocument(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/uploads/legal')]
        string $uploadDir,
    ): Response {
        if (!$this->isCsrfTokenValid('legal_doc_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $case = $this->caseRepo->find($id);
        if ($case === null) { throw $this->createNotFoundException(); }

        $file = $request->files->get('archivo');
        if ($file === null) {
            $this->addFlash('danger', 'No se seleccionó ningún archivo.');
            return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $nombre   = trim((string) $request->request->get('nombre', $file->getClientOriginalName()));
        $safe     = $this->slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = $safe . '-' . uniqid() . '.' . $file->guessExtension();

        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $file->move($uploadDir, $filename);

        $doc = new LegalDocument();
        $doc->setCase($case);
        $doc->setNombre($nombre);
        $doc->setArchivo($filename);
        $doc->setExtension($file->guessExtension());

        $em->persist($doc);
        $em->flush();

        $this->addFlash('success', 'Documento subido.');
        return $this->redirectToRoute('grova_legal_case_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }
}
