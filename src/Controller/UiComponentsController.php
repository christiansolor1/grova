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
            'ui-alerts-swal-catalog',
            'workspace/pages/ui/ui-alerts-swal.html.twig',
        );
    }

    #[Route('/ui-alerts-swal/select-multi-native', name: 'grova_page_ui_alerts_swal_select_multi_native', methods: ['GET'])]
    public function uiAlertsSwalSelectMultiNative(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-alerts-swal-select-multi-native',
            'workspace/pages/ui/ui-alerts-swal-launch.html.twig',
            [
                'swal_demo' => 'selectMultiNative',
                'page_title' => 'alerts_swal.menu_select_multi_native',
                'page_subtitle' => 'alerts_swal.menu_select_multi_native_sub',
            ],
        );
    }

    #[Route('/ui-alerts-swal/select2-multi', name: 'grova_page_ui_alerts_swal_select2_multi', methods: ['GET'])]
    public function uiAlertsSwalSelect2Multi(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-alerts-swal-select2-multi',
            'workspace/pages/ui/ui-alerts-swal-launch.html.twig',
            [
                'swal_demo' => 'select2Multi',
                'page_title' => 'alerts_swal.menu_select2_multi',
                'page_subtitle' => 'alerts_swal.menu_select2_multi_sub',
            ],
        );
    }

    #[Route('/ui-alerts-swal/select-multi-tags', name: 'grova_page_ui_alerts_swal_select_multi_tags', methods: ['GET'])]
    public function uiAlertsSwalSelectMultiTags(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-alerts-swal-select-multi-tags',
            'workspace/pages/ui/ui-alerts-swal-launch.html.twig',
            [
                'swal_demo' => 'selectMultiTags',
                'page_title' => 'alerts_swal.menu_select_multi_tags',
                'page_subtitle' => 'alerts_swal.menu_select_multi_tags_sub',
            ],
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

    #[Route('/ui-tables-wide', name: 'grova_page_ui_tables_wide', methods: ['GET'])]
    public function uiTablesWide(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-tables-wide',
            'workspace/pages/ui/ui-tables-wide.html.twig',
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

    #[Route('/ui-icons-catalog', name: 'grova_page_ui_icons_catalog', methods: ['GET'])]
    public function uiIconsCatalog(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-icons-catalog',
            'workspace/pages/ui/ui-icons-catalog.html.twig',
        );
    }

    #[Route('/ui-modals', name: 'grova_page_ui_modals', methods: ['GET'])]
    public function uiModals(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-modals',
            'workspace/pages/ui/ui-modals.html.twig',
        );
    }

    #[Route('/ui-panels', name: 'grova_page_ui_panels', methods: ['GET'])]
    public function uiPanels(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-panels',
            'workspace/pages/ui/ui-panels.html.twig',
        );
    }

    #[Route('/ui-cards', name: 'grova_page_ui_cards', methods: ['GET'])]
    public function uiCards(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-cards',
            'workspace/pages/ui/ui-cards.html.twig',
        );
    }

    #[Route('/ui-toasts', name: 'grova_page_ui_toasts', methods: ['GET'])]
    public function uiToasts(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-toasts',
            'workspace/pages/ui/ui-toasts.html.twig',
        );
    }

    #[Route('/ui-empty-states', name: 'grova_page_ui_empty_states', methods: ['GET'])]
    public function uiEmptyStates(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-empty-states',
            'workspace/pages/ui/ui-empty-states.html.twig',
        );
    }

    #[Route('/ui-auth', name: 'grova_page_ui_auth', methods: ['GET'])]
    public function uiAuth(Request $request): Response
    {
        return $this->renderUiWorkspacePage($request, 'ui-auth', 'workspace/pages/ui/ui-auth.html.twig');
    }

    #[Route('/ui-user-profile', name: 'grova_page_ui_user_profile', methods: ['GET'])]
    public function uiUserProfile(Request $request): Response
    {
        return $this->renderUiWorkspacePage($request, 'ui-user-profile', 'workspace/pages/ui/ui-user-profile.html.twig');
    }

    #[Route('/ui-permissions', name: 'grova_page_ui_permissions', methods: ['GET'])]
    public function uiPermissions(Request $request): Response
    {
        return $this->renderUiWorkspacePage($request, 'ui-permissions', 'workspace/pages/ui/ui-permissions.html.twig');
    }

    #[Route('/ui-data-display', name: 'grova_page_ui_data_display', methods: ['GET'])]
    public function uiDataDisplay(Request $request): Response
    {
        return $this->renderUiWorkspacePage($request, 'ui-data-display', 'workspace/pages/ui/ui-data-display.html.twig');
    }

    #[Route('/ui-settings', name: 'grova_page_ui_settings', methods: ['GET'])]
    public function uiSettings(Request $request): Response
    {
        return $this->renderUiWorkspacePage($request, 'ui-settings', 'workspace/pages/ui/ui-settings.html.twig');
    }

    #[Route('/ui-admin', name: 'grova_page_ui_admin', methods: ['GET'])]
    public function uiAdmin(Request $request): Response
    {
        return $this->renderUiWorkspacePage($request, 'ui-admin', 'workspace/pages/ui/ui-admin.html.twig');
    }

    #[Route('/ui-api-reference', name: 'grova_page_ui_api_reference', methods: ['GET'])]
    public function uiApiReference(Request $request): Response
    {
        return $this->renderUiWorkspacePage($request, 'ui-api-reference', 'workspace/pages/ui/ui-api-reference.html.twig');
    }

    #[Route('/ui-loading-states', name: 'grova_page_ui_loading_states', methods: ['GET'])]
    public function uiLoadingStates(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-loading-states',
            'workspace/pages/ui/ui-loading-states.html.twig',
        );
    }

    #[Route('/ui-error-pages', name: 'grova_page_ui_error_pages', methods: ['GET'])]
    public function uiErrorPages(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-error-pages',
            'workspace/pages/ui/ui-error-pages.html.twig',
        );
    }

    #[Route('/ui-page-headers', name: 'grova_page_ui_page_headers', methods: ['GET'])]
    public function uiPageHeaders(Request $request): Response
    {
        return $this->renderUiWorkspacePage(
            $request,
            'ui-page-headers',
            'workspace/pages/ui/ui-page-headers.html.twig',
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function renderUiWorkspacePage(
        Request $request,
        string $menuKey,
        string $template,
        array $extra = [],
    ): Response {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $allowed   = $this->menuTreeBuilder->collectKeys($tree);

        if (!\in_array($menuKey, $allowed, true)) {
            throw new NotFoundHttpException();
        }

        return $this->render($template, array_merge([
            'menu_tree'               => $tree,
            'active_menu_key'         => $menuKey,
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ], $extra));
    }
}
