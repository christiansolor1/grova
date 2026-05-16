<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MenuTreeBuilder;
use App\Workspace\WorkspaceLeafRoutes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class WorkspaceController extends AbstractController
{
    #[Route('/', name: 'workspace', methods: ['GET'])]
    public function homeRedirect(Request $request): RedirectResponse
    {
        $route = WorkspaceLeafRoutes::routeNameForMenuKey(MenuTreeBuilder::HOME_MENU_KEY);

        return $this->redirectToRoute($route, [
            '_locale' => $request->getLocale(),
        ], Response::HTTP_FOUND);
    }
}
