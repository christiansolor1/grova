<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\User;
use App\Repository\UserLockRepository;
use App\Service\SectionLockService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes lock TTL information to Twig so JS can set auto-relock timers.
 */
final class LockStatusExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UserLockRepository $lockRepo,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('lock_unlock_data', [$this, 'getUnlockData']),
        ];
    }

    /**
     * Returns an array of [section => seconds_remaining] for all currently
     * unlocked sections. Only includes sections that are actively locked
     * (configured) AND currently unlocked in session.
     *
     * @return array<string, int>  e.g. ['work' => 1740, 'wallet' => 320]
     */
    public function getUnlockData(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $session = $this->requestStack->getSession();
        $lock    = $this->lockRepo->findOneBy(['user' => $user]);

        if ($lock === null) {
            return [];
        }

        $ttl            = $lock->getUnlockTtlMinutes() * 60;
        $lockedSections = $lock->getLockedSections();
        $now            = time();
        $result         = [];

        foreach ($lockedSections as $section) {
            $ts = $session->get('_lock_unlocked_' . $section);
            if ($ts === null) {
                continue;
            }
            $remaining = $ttl - ($now - (int) $ts);
            if ($remaining > 0) {
                $result[$section] = $remaining;
            }
        }

        return $result;
    }
}
