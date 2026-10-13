<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\SectionLockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/lock', name: 'api_lock_')]
class LockApiController extends AbstractController
{
    public function __construct(
        private readonly SectionLockService $lockService,
    ) {}

    #[Route('/settings', name: 'settings', methods: ['GET'])]
    public function settings(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $lock = $this->lockService->getOrCreate($user);

        return $this->json([
            'lockedSections' => $lock->getLockedSections(),
            'ttlMinutes'     => $lock->getUnlockTtlMinutes(),
            'autoPrompt'     => $lock->isWebauthnAutoPrompt(),
        ]);
    }
}
