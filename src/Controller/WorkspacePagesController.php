<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\SuscripcionRepository;
use App\Repository\UserRepository;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use App\Workspace\WorkspaceLeafRoutes;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class WorkspacePagesController extends AbstractController
{
    /** @var array<string, string> menu_key => plantilla Twig del cuerpo */
    private const PAGE_TEMPLATE = [
        'dashboard'            => 'workspace/pages/dashboard.html.twig',
        'users-users'          => 'workspace/pages/_placeholder.html.twig',
        'users-access-control' => 'workspace/pages/_placeholder.html.twig',
        'users-menu-governance' => 'workspace/pages/users/menu-governance.html.twig',
        'menu-manager'         => 'workspace/pages/_placeholder.html.twig',
    ];

    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantContext $tenantContext,
        private readonly SuscripcionRepository $suscripcionRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/dashboard', name: 'grova_page_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $tree    = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $allowed = $this->menuTreeBuilder->collectKeys($tree);

        if (!\in_array('dashboard', $allowed, true)
            && !($allowed === [] && 'dashboard' === MenuTreeBuilder::HOME_MENU_KEY)) {
            throw new NotFoundHttpException();
        }

        $tenant      = $this->tenantContext->getTenant();
        $suscripcion = $tenant !== null ? $this->suscripcionRepository->findActivaForTenant($tenant) : null;
        $modulos     = $this->tenantContext->getModulosActivos();

        // Panel de impersonate — solo visible para ROLE_DEVELOPER y no cuando ya estás impersonando
        $switchUsers = [];
        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH') && !$this->isGranted('IS_IMPERSONATOR')) {
            $currentUsername = $this->getUser()?->getUserIdentifier();
            foreach ($this->userRepository->findAll() as $u) {
                if ($u->getUserIdentifier() !== $currentUsername && $u->getTenant() !== null) {
                    $switchUsers[] = $u;
                }
            }
        }

        return $this->render('workspace/pages/dashboard.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'dashboard',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'tenant'                  => $tenant,
            'suscripcion'             => $suscripcion,
            'modulos_activos'         => $modulos,
            'switch_users'            => $switchUsers,
        ]);
    }

    #[Route('/users-users', name: 'grova_page_users_users', methods: ['GET'])]
    #[Route('/users-access-control', name: 'grova_page_users_access_control', methods: ['GET'])]
    #[Route('/users-menu-governance', name: 'grova_page_users_menu_governance', methods: ['GET'])]
    #[Route('/menu-manager', name: 'grova_page_menu_manager', methods: ['GET'])]
    public function page(Request $request): Response
    {
        $routeName = (string) $request->attributes->get('_route');
        $menuKey   = WorkspaceLeafRoutes::menuKeyForRouteName($routeName);
        if ($menuKey === null) {
            throw new NotFoundHttpException();
        }

        $tree    = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $allowed = $this->menuTreeBuilder->collectKeys($tree);

        if (!\in_array($menuKey, $allowed, true)
            && !($allowed === [] && $menuKey === MenuTreeBuilder::HOME_MENU_KEY)) {
            throw new NotFoundHttpException();
        }

        $template = self::PAGE_TEMPLATE[$menuKey] ?? 'workspace/pages/_placeholder.html.twig';

        return $this->render($template, [
            'menu_tree'                => $tree,
            'active_menu_key'          => $menuKey,
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ]);
    }

    #[Route('/users-menu-governance/save', name: 'grova_page_users_menu_governance_save', methods: ['POST'])]
    public function saveMenuGovernance(Request $request): RedirectResponse
    {
        return $this->saveMenuFromGovernanceForm($request, 'grova_page_users_menu_governance');
    }

    private function saveMenuFromGovernanceForm(Request $request, string $redirectRoute): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('menu_governance_save', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido. Inténtalo de nuevo.');

            return $this->redirectToRoute($redirectRoute, ['_locale' => $request->getLocale()]);
        }

        $mode = (string) $request->request->get('create_mode', 'single');

        try {
            if ($mode === 'bundle') {
                $message = $this->saveBundleMenu($request);
                $this->addFlash('success', $message);
            } else {
                $this->saveSingleMenuItem($request);
                $this->addFlash('success', 'Ítem de menú creado correctamente.');
            }
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('danger', 'El Path ya existe. Usa un Path diferente.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        } catch (\Throwable) {
            $this->addFlash('danger', 'No se pudo guardar el menú. Revisa los datos e intenta de nuevo.');
        }

        return $this->redirectToRoute($redirectRoute, ['_locale' => $request->getLocale()]);
    }

    private function saveSingleMenuItem(Request $request): void
    {
        $key = trim((string) $request->request->get('single_path', ''));
        $label = trim((string) $request->request->get('single_label', ''));
        $icon = trim((string) $request->request->get('single_icon', 'bi-list'));
        $type = (string) $request->request->get('single_item_type', 'submenu');
        $sortOrder = (int) $request->request->get('single_sort_order', 50);
        $parentKey = trim((string) $request->request->get('single_parent_key', ''));
        $status = trim((string) $request->request->get('single_status', 'pendiente'));
        $showInSidebar = $request->request->has('single_show_in_sidebar');
        $devOnly = $request->request->has('single_dev_only');

        if ($key === '' || $label === '') {
            throw new \InvalidArgumentException('Path y Label menú son obligatorios.');
        }
        if (\strlen($key) > 120 || \strlen($label) > 180) {
            throw new \InvalidArgumentException('Path o Label menú exceden la longitud permitida.');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9\\-]*$/', $key)) {
            throw new \InvalidArgumentException('El Path solo permite minúsculas, números y guiones.');
        }
        if (!\in_array($status, ['pendiente', 'hecho'], true)) {
            throw new \InvalidArgumentException('Estado inválido para el ítem.');
        }

        $menu = new Menu();
        $menu->setMenuKey($key);
        $menu->setLabel($label);
        $menu->setIcon($this->normalizeIcon($icon));
        $menu->setSortOrder($sortOrder);
        $menu->setShowInSidebar($showInSidebar);
        $menu->setDevOnly($devOnly);
        $menu->setRequiredRole(null);
        $menu->setStatus($status);

        if ($type === 'principal') {
            $menu->setParentKey(null);
        } else {
            if ($parentKey === '') {
                throw new \InvalidArgumentException('Debes seleccionar un elemento padre.');
            }
            $menu->setParentKey($parentKey);
        }

        $this->entityManager->persist($menu);
        $this->entityManager->flush();
    }

    private function saveBundleMenu(Request $request): string
    {
        $bundleKind = trim((string) $request->request->get('bundle_kind', 'full'));
        $principalKey = trim((string) $request->request->get('bundle_principal_path', ''));
        $principalLabel = trim((string) $request->request->get('bundle_principal_label', ''));
        $submenuKey = trim((string) $request->request->get('bundle_submenu_path', ''));
        $submenuLabel = trim((string) $request->request->get('bundle_submenu_label', ''));
        $suboptionKey = trim((string) $request->request->get('bundle_suboption_path', ''));
        $suboptionLabel = trim((string) $request->request->get('bundle_suboption_label', ''));
        $parentPrincipal = trim((string) $request->request->get('bundle_parent_principal', ''));
        $parentPrincipalSuboption = trim((string) $request->request->get('bundle_parent_principal_suboption', ''));
        $parentSubmenu = trim((string) $request->request->get('bundle_parent_submenu', ''));
        $status = trim((string) $request->request->get('bundle_status', 'pendiente'));
        $baseSort = (int) $request->request->get('bundle_base_sort_order', 80);

        if (!\in_array($bundleKind, ['full', 'submenu', 'subopcion'], true)) {
            throw new \InvalidArgumentException('Tipo de estructura inválido.');
        }
        if (!\in_array($status, ['pendiente', 'hecho'], true)) {
            throw new \InvalidArgumentException('Estado inválido para la estructura.');
        }

        $required = [];
        if ($bundleKind === 'full') {
            $required = [
                'Path menú principal' => $principalKey,
                'Label menú principal' => $principalLabel,
                'Path submenú' => $submenuKey,
                'Label submenú' => $submenuLabel,
                'Path subopción' => $suboptionKey,
                'Label subopción' => $suboptionLabel,
            ];
        } elseif ($bundleKind === 'submenu') {
            $required = [
                'Menú principal padre' => $parentPrincipal,
                'Path submenú' => $submenuKey,
                'Label submenú' => $submenuLabel,
                'Path subopción' => $suboptionKey,
                'Label subopción' => $suboptionLabel,
            ];
        } else {
            $required = [
                'Menú principal padre' => $parentPrincipalSuboption,
                'Submenú padre' => $parentSubmenu,
                'Path subopción' => $suboptionKey,
                'Label subopción' => $suboptionLabel,
            ];
        }
        foreach ($required as $field => $value) {
            if ($value === '') {
                throw new \InvalidArgumentException($field.' es obligatorio.');
            }
        }

        $keys = [];
        if ($bundleKind === 'full') {
            $keys = [$principalKey, $submenuKey, $suboptionKey];
        } elseif ($bundleKind === 'submenu') {
            $keys = [$submenuKey, $suboptionKey];
        } else {
            $keys = [$suboptionKey];
        }
        $uniqueKeys = \count(array_unique($keys));
        if ($bundleKind === 'full' && $uniqueKeys !== 3) {
            throw new \InvalidArgumentException('Los Path del menú completo deben ser diferentes.');
        }
        if ($bundleKind === 'submenu' && $uniqueKeys !== 2) {
            throw new \InvalidArgumentException('Los Path del submenú y subopción deben ser diferentes.');
        }
        foreach ($keys as $k) {
            if (!preg_match('/^[a-z0-9][a-z0-9\\-]*$/', $k)) {
                throw new \InvalidArgumentException('Los Path solo permiten minúsculas, números y guiones.');
            }
        }

        if ($bundleKind === 'full') {
            $principal = (new Menu())
                ->setMenuKey($principalKey)
                ->setParentKey(null)
                ->setLabel($principalLabel)
                ->setIcon('bi-folder')
                ->setSortOrder($baseSort)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status);

            $submenu = (new Menu())
                ->setMenuKey($submenuKey)
                ->setParentKey($principalKey)
                ->setLabel($submenuLabel)
                ->setIcon('bi-list')
                ->setSortOrder($baseSort + 1)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status);

            $suboption = (new Menu())
                ->setMenuKey($suboptionKey)
                ->setParentKey($submenuKey)
                ->setLabel($suboptionLabel)
                ->setIcon('bi-dot')
                ->setSortOrder($baseSort + 2)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status);

            $this->entityManager->persist($principal);
            $this->entityManager->persist($submenu);
            $this->entityManager->persist($suboption);
            $this->entityManager->flush();

            return 'Estructura creada: menú principal + submenú + subopción.';
        }

        if ($bundleKind === 'submenu') {
            $principalExists = $this->entityManager->getRepository(Menu::class)->findOneBy(['menuKey' => $parentPrincipal]);
            if ($principalExists === null) {
                throw new \InvalidArgumentException('El menú principal padre no existe.');
            }

            $submenu = (new Menu())
                ->setMenuKey($submenuKey)
                ->setParentKey($parentPrincipal)
                ->setLabel($submenuLabel)
                ->setIcon('bi-list')
                ->setSortOrder($baseSort)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status);

            $suboption = (new Menu())
                ->setMenuKey($suboptionKey)
                ->setParentKey($submenuKey)
                ->setLabel($suboptionLabel)
                ->setIcon('bi-dot')
                ->setSortOrder($baseSort + 1)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status);

            $this->entityManager->persist($submenu);
            $this->entityManager->persist($suboption);
            $this->entityManager->flush();

            return 'Estructura creada: submenú + subopción.';
        }

        $submenuParent = $this->entityManager->getRepository(Menu::class)->findOneBy(['menuKey' => $parentSubmenu]);
        if ($submenuParent === null) {
            throw new \InvalidArgumentException('El submenú padre no existe.');
        }
        if ($submenuParent->getParentKey() !== $parentPrincipalSuboption) {
            throw new \InvalidArgumentException('El submenú seleccionado no pertenece al menú principal indicado.');
        }

        $suboption = (new Menu())
            ->setMenuKey($suboptionKey)
            ->setParentKey($parentSubmenu)
            ->setLabel($suboptionLabel)
            ->setIcon('bi-dot')
            ->setSortOrder($baseSort)
            ->setShowInSidebar(true)
            ->setDevOnly(false)
            ->setRequiredRole(null)
            ->setStatus($status);

        $this->entityManager->persist($suboption);
        $this->entityManager->flush();

        return 'Estructura creada: subopción bajo submenú existente.';
    }

    private function normalizeIcon(string $icon): string
    {
        $raw = strtolower(trim($icon));
        if ($raw === '') {
            return 'bi-list';
        }

        $safe = preg_replace('/[^a-z0-9\-\s]/', '', $raw) ?? '';
        $safe = trim($safe);
        if ($safe === '') {
            return 'bi-list';
        }

        preg_match('/\bbi-[a-z0-9-]+\b/', $safe, $biMatch);
        if (isset($biMatch[0])) {
            return $biMatch[0];
        }

        preg_match('/\bfa-[a-z0-9-]+\b/', $safe, $faMatch);
        if (isset($faMatch[0])) {
            preg_match('/\bfa-(solid|regular|brands|light|duotone)\b/', $safe, $faStyleMatch);
            $style = isset($faStyleMatch[0]) ? $faStyleMatch[0] : 'fa-solid';

            return $style.' '.$faMatch[0];
        }

        $legacyMap = [
            'dashboard' => 'bi-grid',
            'users' => 'bi-people',
            'list' => 'bi-list',
            'shield' => 'bi-shield',
            'plug' => 'bi-plug',
            'check' => 'bi-check2',
            'edit' => 'bi-pencil',
            'alert' => 'bi-exclamation-triangle',
            'settings' => 'bi-gear',
            'layout' => 'bi-layout-sidebar',
            'user' => 'bi-person',
            'dot' => 'bi-dot',
            'folder' => 'bi-folder',
        ];
        if (isset($legacyMap[$safe])) {
            return $legacyMap[$safe];
        }

        if (preg_match('/^[a-z0-9-]+$/', $safe)) {
            return 'bi-'.$safe;
        }

        throw new \InvalidArgumentException('Ícono inválido. Usa Bootstrap Icons (bi-*) o Font Awesome (fa-*).');
    }
}
