<?php

namespace App\Controller;

use App\Service\MenuTreeBuilder;
use App\Service\Setup\EstadoInstalacion;
use App\Workspace\WorkspaceLeafRoutes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly EstadoInstalacion $estadoInstalacion,
    ) {
    }

    public function home(): Response
    {
        if (!$this->estadoInstalacion->estaInstalado()) {
            return $this->redirectToRoute('app_setup');
        }

        if ($this->getUser()) {
            $route = WorkspaceLeafRoutes::routeNameForMenuKey(MenuTreeBuilder::HOME_MENU_KEY);

            return $this->redirectToRoute($route, ['_locale' => 'es']);
        }

        return $this->redirectToRoute('login', ['_locale' => 'es']);
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, RateLimiterFactoryInterface $loginAttemptLimiter): Response
    {
        if ($this->getUser()) {
            $route = WorkspaceLeafRoutes::routeNameForMenuKey(MenuTreeBuilder::HOME_MENU_KEY);

            return $this->redirectToRoute($route, ['_locale' => $request->getLocale()]);
        }

        $limiter      = $loginAttemptLimiter->create($request->getClientIp());
        $limit        = $limiter->consume(0);
        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $attemptsLeft = $limit->getRemainingTokens();
        $retryAfter   = !$limit->isAccepted() ? $limit->getRetryAfter() : null;

        $postLoginRoute = WorkspaceLeafRoutes::routeNameForMenuKey(MenuTreeBuilder::HOME_MENU_KEY);
        $postLoginPath   = $this->generateUrl($postLoginRoute, ['_locale' => $request->getLocale()]);

        return $this->render('login/indexLogin.html.twig', [
            'last_username'   => $lastUsername,
            'error'           => $error,
            'attempts_left'   => $attemptsLeft,
            'retry_after'     => $retryAfter,
            'post_login_path' => $postLoginPath,
        ]);
    }

    #[Route('/login/check', name: 'login_check', methods: ['POST'])]
    public function loginCheck(): never
    {
        throw new \LogicException('Este método no debería ejecutarse.');
    }
}
