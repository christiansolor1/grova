<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Core\Contact\Entity\Contact;
use App\Module\Core\Contact\Repository\ContactRepository;
use App\Service\MenuTreeBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/contacts', name: 'grova_contacts_')]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly ContactRepository $contactRepo,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $tipo = $request->query->get('tipo', '');
        $q    = trim((string) $request->query->get('q', ''));

        if ($q !== '') {
            $contacts = $this->contactRepo->search($q);
        } elseif ($tipo !== '') {
            $contacts = $this->contactRepo->findByTipo($tipo);
        } else {
            $contacts = $this->contactRepo->findAllOrdered();
        }

        return $this->render('workspace/pages/contacts/index.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'contactos',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'contacts'                => $contacts,
            'tipo_filter'             => $tipo,
            'q'                       => $q,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('contact_create', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_contacts_index', ['_locale' => $request->getLocale()]);
        }

        $contact = new Contact();
        $this->fillFromRequest($contact, $request);

        $em->persist($contact);
        $em->flush();

        $this->addFlash('success', sprintf('Contacto "%s" creado.', $contact->getNombreCompleto()));
        return $this->redirectToRoute('grova_contacts_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    public function edit(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('contact_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_contacts_index', ['_locale' => $request->getLocale()]);
        }

        $contact = $this->contactRepo->find($id);
        if ($contact === null) { throw $this->createNotFoundException(); }

        $this->fillFromRequest($contact, $request);
        $em->flush();

        $this->addFlash('success', 'Contacto actualizado.');
        return $this->redirectToRoute('grova_contacts_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('contact_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_contacts_index', ['_locale' => $request->getLocale()]);
        }

        $contact = $this->contactRepo->find($id);
        if ($contact !== null) {
            $contact->setActivo(false);
            $em->flush();
            $this->addFlash('success', 'Contacto archivado.');
        }

        return $this->redirectToRoute('grova_contacts_index', ['_locale' => $request->getLocale()]);
    }

    /** API: búsqueda para Select2 en otros módulos */
    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function apiSearch(Request $request): JsonResponse
    {
        $q    = trim((string) $request->query->get('q', ''));
        $tipo = $request->query->get('tipo', '');

        $contacts = $q !== ''
            ? $this->contactRepo->search($q)
            : ($tipo !== '' ? $this->contactRepo->findByTipo($tipo) : []);

        return $this->json(array_map(fn(Contact $c) => [
            'id'   => $c->getId(),
            'text' => $c->getNombreCompleto() . ($c->getEmpresa() ? ' — ' . $c->getEmpresa() : ''),
        ], $contacts));
    }

    private function fillFromRequest(Contact $contact, Request $request): void
    {
        $contact->setTipo((string) $request->request->get('tipo', 'cliente'));
        $contact->setNombre(trim((string) $request->request->get('nombre', '')));
        $contact->setApellido(trim((string) $request->request->get('apellido', '')) ?: null);
        $contact->setEmpresa(trim((string) $request->request->get('empresa', '')) ?: null);
        $contact->setEmail(trim((string) $request->request->get('email', '')) ?: null);
        $contact->setTelefono(trim((string) $request->request->get('telefono', '')) ?: null);
        $contact->setDireccion(trim((string) $request->request->get('direccion', '')) ?: null);
        $contact->setCiudad(trim((string) $request->request->get('ciudad', '')) ?: null);
        $contact->setPais(trim((string) $request->request->get('pais', '')) ?: null);
        $contact->setNotas(trim((string) $request->request->get('notas', '')) ?: null);
    }
}
