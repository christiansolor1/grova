<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        $data = array_map(fn(User $user) => [
            'id'       => $user->getId(),
            'email'    => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles'    => $user->getRoles(),
        ], $users);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json([
            'id'       => $user->getId(),
            'email'    => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles'    => $user->getRoles(),
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        // Solo el propio usuario o SUPER_ADMIN pueden modificar
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() !== $user->getId() && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->json(['error' => 'No tienes permiso para modificar este usuario.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($data['email'])) {
            if (!filter_var($data['email'], \FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'Email inválido.'], Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail($data['email']);
        }

        if (!empty($data['username'])) {
            $user->setUsername($data['username']);
        }

        if (!empty($data['password'])) {
            if (\strlen($data['password']) < 8) {
                return $this->json(['error' => 'La contraseña debe tener al menos 8 caracteres.'], Response::HTTP_BAD_REQUEST);
            }
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $entityManager->flush();

        return $this->json(['message' => 'Usuario actualizado.']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // No permitir borrarse a sí mismo
        if ($currentUser->getId() === $user->getId()) {
            return $this->json(['error' => 'No puedes eliminar tu propia cuenta.'], Response::HTTP_FORBIDDEN);
        }

        // Solo SUPER_ADMIN puede eliminar usuarios
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->json(['error' => 'No tienes permiso para eliminar usuarios.'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'Usuario eliminado.']);
    }
}