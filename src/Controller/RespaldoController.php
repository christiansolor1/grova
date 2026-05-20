<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MenuTreeBuilder;
use App\Service\Respaldo\RespaldoModuloService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/respaldo', name: 'grova_respaldo_')]
final class RespaldoController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly RespaldoModuloService $respaldoService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function indice(): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('workspace/pages/respaldo/indexRespaldo.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'respaldo',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'modulos'                 => $this->respaldoService->getModulos(),
        ]);
    }

    #[Route('/{modulo}/exportar', name: 'exportar', methods: ['GET'])]
    public function exportar(string $modulo): Response
    {
        try {
            return $this->respaldoService->generarRespaldo($modulo);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('grova_respaldo_index', ['_locale' => 'es']);
        }
    }

    #[Route('/{modulo}/importar', name: 'importar', methods: ['POST'])]
    public function importar(string $modulo, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('respaldo_importar_' . $modulo, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_respaldo_index', ['_locale' => 'es']);
        }

        /** @var UploadedFile|null $archivo */
        $archivo = $request->files->get('respaldo_zip');

        if ($archivo === null || !$archivo->isValid()) {
            $this->addFlash('danger', 'Debes seleccionar un archivo ZIP válido.');

            return $this->redirectToRoute('grova_respaldo_index', ['_locale' => 'es']);
        }

        if ($archivo->getClientOriginalExtension() !== 'zip' && $archivo->guessExtension() !== 'zip') {
            $this->addFlash('danger', 'El archivo debe tener extensión .zip.');

            return $this->redirectToRoute('grova_respaldo_index', ['_locale' => 'es']);
        }

        try {
            $mensaje = $this->respaldoService->procesarRespaldo(
                (string) $archivo->getRealPath(),
                $modulo
            );
            $this->addFlash('success', $mensaje);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('grova_respaldo_index', ['_locale' => 'es']);
    }
}
