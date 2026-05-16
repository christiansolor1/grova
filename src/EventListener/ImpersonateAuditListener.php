<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

#[AsEventListener(event: 'security.switch_user')]
final class ImpersonateAuditListener
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function __invoke(SwitchUserEvent $event): void
    {
        $request     = $event->getRequest();
        $targetUser  = $event->getTargetUser();
        $impersonator = $request->getSession()->get('_security_main');

        $this->logger->warning('IMPERSONATE', [
            'target'     => $targetUser->getUserIdentifier(),
            'ip'         => $request->getClientIp(),
            'at'         => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
