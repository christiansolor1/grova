<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserLockRepository;
use App\Service\MenuTreeBuilder;
use App\Service\SectionLockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly UserLockRepository $userLockRepo,
    ) {
    }

    #[Route('/profile', name: 'grova_profile', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        /** @var User $user */
        $user     = $this->getUser();
        $userLock = $this->userLockRepo->findOneBy(['user' => $user]);

        return $this->render('workspace/pages/profile/index.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'profile-user',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'user_lock'               => $userLock,
            'lock_sections'           => SectionLockService::SECTIONS,
        ]);
    }
}
