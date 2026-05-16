<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Service\MenuTreeBuilder;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/notifications', name: 'notifications_')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $service,
        private readonly MenuTreeBuilder $menuTreeBuilder,
    ) {}

    /** Inbox para el dropdown del navbar */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'unread' => $this->service->countUnread($user),
            'items'  => array_map(static fn(Notification $n) => [
                'id'          => $n->getId(),
                'title'       => $n->getTitle(),
                'body'        => $n->getBody(),
                'icon'        => $n->getIcon(),
                'url'         => $n->getUrl(),
                'module'      => $n->getModule(),
                'type'        => $n->getType(),
                'typeColor'   => $n->getTypeEnum()->color(),
                'read'        => $n->isRead(),
                'createdAt'   => $n->getCreatedAt()->format('c'),
            ], $this->service->findInbox($user)),
        ]);
    }

    /** Marcar todas como leídas */
    #[Route('/read', name: 'mark_read', methods: ['POST'])]
    public function markRead(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->service->markAllRead($user);

        return $this->json(['unread' => 0]);
    }

    /** Descartar una notificación del inbox (sigue en historial) */
    #[Route('/{id}/dismiss', name: 'dismiss', methods: ['POST'])]
    public function dismiss(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $ok   = $this->service->dismiss($id, $user);

        return $this->json([
            'ok'     => $ok,
            'unread' => $this->service->countUnread($user),
        ]);
    }

    /** Descartar todo el inbox */
    #[Route('/dismiss-all', name: 'dismiss_all', methods: ['POST'])]
    public function dismissAll(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->service->dismissAll($user);

        return $this->json(['unread' => 0]);
    }

    /** Página de historial completo */
    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(Request $request): Response
    {
        /** @var User $user */
        $user    = $this->getUser();
        $page    = max(1, (int) $request->query->get('page', 1));
        $module  = $request->query->get('module') ?: null;
        $type    = $request->query->get('type') ?: null;
        $perPage = 20;

        $items   = $this->service->findHistory($user, $page, $module, $type);
        $total   = $this->service->countHistory($user, $module, $type);
        $pages   = (int) ceil($total / $perPage);
        $modules = $this->service->findDistinctModules($user);
        $types   = NotificationType::cases();

        return $this->render('workspace/pages/notifications/history.html.twig', [
            'menu_tree'               => $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER')),
            'active_menu_key'         => '',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'items'                   => $items,
            'total'                   => $total,
            'page'                    => $page,
            'pages'                   => $pages,
            'modules'                 => $modules,
            'types'                   => $types,
            'filter_module'           => $module,
            'filter_type'             => $type,
        ]);
    }
}
