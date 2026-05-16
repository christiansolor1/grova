<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Twig\NotificationExtension;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds X-Unread-Notifications header to every authenticated response.
 * Reuses the count already cached by NotificationExtension to avoid a double query.
 */
class NotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly NotificationExtension $notificationExtension,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // getUnreadCount() is cached — no extra DB query if Twig already called it
        $count = $this->notificationExtension->getUnreadCount();
        $event->getResponse()->headers->set('X-Unread-Notifications', (string) $count);
    }
}
