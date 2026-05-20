<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/usuarios', name: 'grova_usuarios_')]
final class ControladorUsuariosAdmin extends AbstractController
{
    private const ROLES_ETIQUETAS = [
        'ROLE_SUPER_ADMIN' => 'Super Admin',
        'ROLE_DEVELOPER' => 'Developer',
        'ROLE_ADMIN' => 'Admin',
        'ROLE_USER' => 'Usuario',
    ];

    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly TenantRepository $tenantRepo,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TenantContext $tenantContext,
        private readonly MenuTreeBuilder $menuTreeBuilder,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function indice(): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        if ($this->esGestorGlobal()) {
            $usuarios = $this->userRepo->findAllOrdered();
        } else {
            $tenant = $this->tenantContext->getTenant();
            $usuarios = $tenant !== null ? $this->userRepo->findByTenant($tenant) : [];
        }

        return $this->render('admin/usuarios/indexUsuarios.html.twig', [
            'menu_tree' => $tree,
            'active_menu_key' => 'usuarios',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'usuarios' => $usuarios,
            'es_gestor_global' => $this->esGestorGlobal(),
        ]);
    }

    #[Route('/crear', name: 'crear', methods: ['GET'])]
    public function crear(): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('admin/usuarios/form.html.twig', [
            'menu_tree' => $tree,
            'active_menu_key' => 'usuarios',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'usuario' => null,
            'es_gestor_global' => $this->esGestorGlobal(),
            'tenants' => $this->esGestorGlobal() ? $this->tenantRepo->findAll() : [],
            'roles_disponibles' => $this->getRolesDisponibles(),
        ]);
    }

    #[Route('/crear', name: 'guardar', methods: ['POST'])]
    public function guardar(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('crear_usuario', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_usuarios_index', ['_locale' => $request->getLocale()]);
        }

        $email = trim((string) $request->request->get('email', ''));
        $username = trim((string) $request->request->get('username', ''));
        $password = (string) $request->request->get('password', '');
        $nombre = trim((string) $request->request->get('nombre', ''));
        $apellido = trim((string) $request->request->get('apellido', ''));

        if ($email === '' || $username === '' || $password === '') {
            $this->addFlash('danger', 'Email, usuario y contraseña son obligatorios.');

            return $this->redirectToRoute('grova_usuarios_crear', ['_locale' => $request->getLocale()]);
        }

        // Validar email único
        $existente = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existente !== null) {
            $this->addFlash('danger', 'Ya existe un usuario con ese email.');

            return $this->redirectToRoute('grova_usuarios_crear', ['_locale' => $request->getLocale()]);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setNombre($nombre !== '' ? $nombre : null);
        $user->setApellido($apellido !== '' ? $apellido : null);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        // Asignar tenant
        if ($this->esGestorGlobal()) {
            $tenantId = (int) $request->request->get('tenant_id', '0');
            $tenant = $tenantId > 0 ? $this->tenantRepo->find($tenantId) : null;
            $user->setTenant($tenant);

            // Asignar roles
            /** @var list<string> $roles */
            $roles = array_values(array_filter((array) $request->request->all('roles')));
            $roles = array_intersect($roles, array_keys(self::ROLES_ETIQUETAS));
            $user->setRoles($roles);
        } else {
            $tenant = $this->tenantContext->getTenant();
            $user->setTenant($tenant);
            $user->setRoles(['ROLE_USER']);
        }

        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash('success', 'Usuario creado correctamente.');

        return $this->redirectToRoute('grova_usuarios_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/{id}/editar', name: 'editar', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editar(int $id, Request $request): Response
    {
        $usuario = $this->em->getRepository(User::class)->find($id);
        if ($usuario === null) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        if (!$this->puedeAccederAlUsuario($usuario)) {
            throw $this->createAccessDeniedException('No tienes permiso para editar este usuario.');
        }

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('admin/usuarios/form.html.twig', [
            'menu_tree' => $tree,
            'active_menu_key' => 'usuarios',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'usuario' => $usuario,
            'es_gestor_global' => $this->esGestorGlobal(),
            'tenants' => $this->esGestorGlobal() ? $this->tenantRepo->findAll() : [],
            'roles_disponibles' => $this->getRolesDisponibles(),
        ]);
    }

    #[Route('/{id}/editar', name: 'actualizar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function actualizar(int $id, Request $request): Response
    {
        $usuario = $this->em->getRepository(User::class)->find($id);
        if ($usuario === null) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        if (!$this->puedeAccederAlUsuario($usuario)) {
            throw $this->createAccessDeniedException('No tienes permiso para editar este usuario.');
        }

        if (!$this->isCsrfTokenValid('editar_usuario_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_usuarios_index', ['_locale' => $request->getLocale()]);
        }

        $email = trim((string) $request->request->get('email', ''));
        $username = trim((string) $request->request->get('username', ''));
        $password = (string) $request->request->get('password', '');
        $nombre = trim((string) $request->request->get('nombre', ''));
        $apellido = trim((string) $request->request->get('apellido', ''));

        if ($email === '' || $username === '') {
            $this->addFlash('danger', 'Email y usuario son obligatorios.');

            return $this->redirectToRoute('grova_usuarios_editar', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        // Validar email único (excluyendo este usuario)
        $existente = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existente !== null && $existente->getId() !== $id) {
            $this->addFlash('danger', 'Ya existe otro usuario con ese email.');

            return $this->redirectToRoute('grova_usuarios_editar', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $usuario->setEmail($email);
        $usuario->setUsername($username);
        $usuario->setNombre($nombre !== '' ? $nombre : null);
        $usuario->setApellido($apellido !== '' ? $apellido : null);

        if ($password !== '') {
            $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $password));
        }

        // Asignar tenant y roles (solo gestor global)
        if ($this->esGestorGlobal()) {
            $tenantId = (int) $request->request->get('tenant_id', '0');
            $tenant = $tenantId > 0 ? $this->tenantRepo->find($tenantId) : null;
            $usuario->setTenant($tenant);

            /** @var list<string> $roles */
            $roles = array_values(array_filter((array) $request->request->all('roles')));
            $roles = array_intersect($roles, array_keys(self::ROLES_ETIQUETAS));
            $usuario->setRoles($roles);
        }

        $this->em->flush();

        $this->addFlash('success', 'Usuario actualizado correctamente.');

        return $this->redirectToRoute('grova_usuarios_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/{id}/eliminar', name: 'eliminar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function eliminar(int $id, Request $request): Response
    {
        $usuario = $this->em->getRepository(User::class)->find($id);
        if ($usuario === null) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        if (!$this->puedeAccederAlUsuario($usuario)) {
            throw $this->createAccessDeniedException('No tienes permiso para eliminar este usuario.');
        }

        if (!$this->isCsrfTokenValid('eliminar_usuario_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_usuarios_index', ['_locale' => $request->getLocale()]);
        }

        // No permitir eliminarse a sí mismo
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $id) {
            $this->addFlash('danger', 'No puedes eliminar tu propio usuario.');

            return $this->redirectToRoute('grova_usuarios_index', ['_locale' => $request->getLocale()]);
        }

        $this->em->remove($usuario);
        $this->em->flush();

        $this->addFlash('success', 'Usuario eliminado correctamente.');

        return $this->redirectToRoute('grova_usuarios_index', ['_locale' => $request->getLocale()]);
    }

    private function esGestorGlobal(): bool
    {
        return $this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_DEVELOPER');
    }

    private function puedeAccederAlUsuario(User $usuario): bool
    {
        if ($this->esGestorGlobal()) {
            return true;
        }

        // ROLE_ADMIN solo puede acceder a usuarios de su propio tenant
        $miTenant = $this->tenantContext->getTenant();
        if ($miTenant === null) {
            return false;
        }

        $tenantUsuario = $usuario->getTenant();

        return $tenantUsuario !== null && $tenantUsuario->getId() === $miTenant->getId();
    }

    /**
     * @return array<string, string>
     */
    private function getRolesDisponibles(): array
    {
        if ($this->esGestorGlobal()) {
            return self::ROLES_ETIQUETAS;
        }

        // ROLE_ADMIN solo puede asignar ROLE_USER
        return ['ROLE_USER' => 'Usuario'];
    }
}
