<?php

namespace App\Security;

use App\Service\MenuTreeBuilder;
use App\Workspace\WorkspaceLeafRoutes;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private RouterInterface $router) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $locale = $request->getLocale() ?: 'es';
        $route  = WorkspaceLeafRoutes::routeNameForMenuKey(MenuTreeBuilder::HOME_MENU_KEY);

        return new RedirectResponse(
            $this->router->generate($route, ['_locale' => $locale]),
            302
        );
    }
}
