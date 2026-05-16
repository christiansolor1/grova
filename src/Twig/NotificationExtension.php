<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    private ?int $cachedCount = null;

    public function __construct(
        private readonly NotificationService $service,
        private readonly Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_notifications_count', $this->getUnreadCount(...)),
        ];
    }

    public function getUnreadCount(): int
    {
        if ($this->cachedCount !== null) {
            return $this->cachedCount;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->cachedCount = 0;
        }

        return $this->cachedCount = $this->service->countUnread($user);
    }
}
