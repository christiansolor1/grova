<?php

namespace App\EventSubscriber;

use App\Service\MenuTreeBuilder;
use App\Workspace\WorkspaceLeafRoutes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NotFoundSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $request   = $event->getRequest();
        $token     = $this->tokenStorage->getToken();
        $user      = $token?->getUser();
        $locale    = $request->getLocale() ?: 'es';
        $isAuthenticated = $user !== null;

        if ($isAuthenticated) {
            $route = WorkspaceLeafRoutes::routeNameForMenuKey(MenuTreeBuilder::HOME_MENU_KEY);
            $url   = $this->router->generate($route, ['_locale' => $locale]);
        } else {
            $url = $this->router->generate('login', ['_locale' => $locale]);
        }

        $event->setResponse(new RedirectResponse($url, 302));
        $event->stopPropagation();
    }
}
