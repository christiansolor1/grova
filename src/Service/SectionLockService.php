<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserLock;
use App\Repository\UserLockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SectionLockService
{
    public const SECTIONS = [
        'work'         => 'Work',
        'wallet'       => 'Wallet',
        'legal'        => 'Legal',
        'fishing'      => 'Pesca',
        'construccion' => 'Construcción',
        'profile'      => 'Perfil',
    ];

    /** Route-prefix → section key */
    private const ROUTE_MAP = [
        'grova_work_'         => 'work',
        'grova_wallet_'       => 'wallet',
        'grova_legal_'        => 'legal',
        'grova_fishing_'      => 'fishing',
        'grova_construccion_' => 'construccion',
        'grova_profile'       => 'profile',
    ];

    public function __construct(
        private readonly UserLockRepository $repo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getSectionFromRoute(string $route): ?string
    {
        foreach (self::ROUTE_MAP as $prefix => $section) {
            if (str_starts_with($route, $prefix)) {
                return $section;
            }
        }
        return null;
    }

    public function getOrCreate(User $user): UserLock
    {
        $lock = $this->repo->findOneBy(['user' => $user]);
        if ($lock === null) {
            $lock = new UserLock();
            $lock->setUser($user);
            $this->em->persist($lock);
            $this->em->flush();
        }
        return $lock;
    }

    public function isSectionLocked(User $user, string $section): bool
    {
        $lock = $this->repo->findOneBy(['user' => $user]);
        if ($lock === null) return false;
        return in_array($section, $lock->getLockedSections(), true);
    }

    public function isSessionUnlocked(SessionInterface $session, User $user, string $section): bool
    {
        $ts = $session->get('_lock_unlocked_' . $section);
        if ($ts === null) return false;

        $lock = $this->repo->findOneBy(['user' => $user]);
        $ttl  = $lock ? $lock->getUnlockTtlMinutes() * 60 : 1800;

        return (time() - (int) $ts) < $ttl;
    }

    public function unlockSection(SessionInterface $session, string $section): void
    {
        $session->set('_lock_unlocked_' . $section, time());
    }

    public function lockSection(SessionInterface $session, string $section): void
    {
        $session->remove('_lock_unlocked_' . $section);
    }

    public function setPin(UserLock $lock, string $pin): void
    {
        $lock->setPinHash(password_hash($pin, PASSWORD_BCRYPT));
        $this->em->flush();
    }

    public function saveSettings(UserLock $lock, array $sections, int $ttl, bool $autoPrompt = true): void
    {
        $lock->setLockedSections(array_values(array_intersect($sections, array_keys(self::SECTIONS))));
        $lock->setUnlockTtlMinutes(max(1, min(1440, $ttl)));
        $lock->setWebauthnAutoPrompt($autoPrompt);
        $this->em->flush();
    }
}
