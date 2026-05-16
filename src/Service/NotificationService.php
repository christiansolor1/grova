<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
// MERCURE: descomentar cuando el Hub esté corriendo
// use Symfony\Component\Mercure\HubInterface;
// use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationRepository $repo,
        // MERCURE: inyectar cuando el Hub esté corriendo
        // private readonly HubInterface $hub,
    ) {}

    public function notify(
        User $user,
        string $title,
        string $body,
        ?string $url = null,
        ?string $module = null,
        ?string $icon = null,
        string $type = 'info',
        array $context = [],
    ): Notification {
        $n = new Notification();
        $n->setUser($user)
          ->setTitle($title)
          ->setBody($body)
          ->setUrl($url)
          ->setModule($module)
          ->setIcon($icon)
          ->setType($type)
          ->setTenantSlug($user->getTenant()?->getSlug())
          ->setContext($context ?: null);

        $this->em->persist($n);
        $this->em->flush();

        // MERCURE: descomentar cuando el Hub esté corriendo
        // $this->hub->publish(new Update(
        //     topics: ['/user/' . $user->getId()],
        //     data: json_encode([
        //         'title'  => $title,
        //         'body'   => $body,
        //         'type'   => $type,
        //         'unread' => $this->countUnread($user),
        //     ]),
        // ));

        return $n;
    }

    public function countUnread(User $user): int
    {
        return $this->repo->countUnread($user);
    }

    /** @return Notification[] */
    public function findInbox(User $user, int $limit = 20): array
    {
        return $this->repo->findInbox($user, $limit);
    }

    /** @return Notification[] */
    public function findHistory(User $user, int $page = 1, ?string $module = null, ?string $type = null): array
    {
        return $this->repo->findHistory($user, $page, 20, $module, $type);
    }

    public function countHistory(User $user, ?string $module = null, ?string $type = null): int
    {
        return $this->repo->countHistory($user, $module, $type);
    }

    /** @return string[] */
    public function findDistinctModules(User $user): array
    {
        return $this->repo->findDistinctModules($user);
    }

    public function markAllRead(User $user): void
    {
        $this->repo->markAllRead($user);
    }

    public function dismiss(int $id, User $user): bool
    {
        $n = $this->repo->find($id);
        if ($n === null || $n->getUser()->getId() !== $user->getId()) {
            return false;
        }
        if (!$n->isDismissed()) {
            $n->setDismissedAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        return true;
    }

    public function dismissAll(User $user): void
    {
        $this->repo->dismissAll($user);
    }
}
