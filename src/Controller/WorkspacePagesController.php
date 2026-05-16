<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MenuTreeBuilder;
use App\Workspace\WorkspaceLeafRoutes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        'menu-manager'         => 'workspace/pages/_placeholder.html.twig',
    ];

    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
    ) {
    }

    #[Route('/dashboard', name: 'grova_page_dashboard', methods: ['GET'])]
    #[Route('/users-users', name: 'grova_page_users_users', methods: ['GET'])]
    #[Route('/users-access-control', name: 'grova_page_users_access_control', methods: ['GET'])]
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
}
