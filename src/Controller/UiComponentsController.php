<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MenuTreeBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Módulo de referencia de UI: una acción y una plantilla por pantalla.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class UiComponentsController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
    ) {
    }

    #[Route('/ui-buttons', name: 'grova_page_ui_buttons', methods: ['GET'])]
    public function uiButtons(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-buttons',
            'workspace/pages/ui/ui-buttons.html.twig',
        );
    }

    #[Route('/ui-inputs', name: 'grova_page_ui_inputs', methods: ['GET'])]
    public function uiInputs(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-inputs',
            'workspace/pages/ui/ui-inputs.html.twig',
        );
    }

    #[Route('/ui-alerts-swal', name: 'grova_page_ui_alerts_swal', methods: ['GET'])]
    public function uiAlertsSwal(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-alerts-swal',
            'workspace/pages/ui/ui-alerts-swal.html.twig',
        );
    }

    #[Route('/ui-tables', name: 'grova_page_ui_tables', methods: ['GET'])]
    public function uiTables(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-tables',
            'workspace/pages/ui/ui-tables.html.twig',
        );
    }

    #[Route('/ui-status', name: 'grova_page_ui_status', methods: ['GET'])]
    public function uiStatus(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-status',
            'workspace/pages/ui/ui-status.html.twig',
        );
    }

    private function renderUiWorkspacePage(
        Request $request,
        string $menuKey,
        string $template,
    ): Response {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $allowed   = $this->menuTreeBuilder->collectKeys($tree);

        if (!\in_array($menuKey, $allowed, true)) {
            throw new NotFoundHttpException();
        }

        return $this->render($template, [
            'menu_tree'               => $tree,
            'active_menu_key'         => $menuKey,
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ]);
    }
}
