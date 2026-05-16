<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:role',
    description: 'Asigna o revoca un rol a un usuario',
)]
class UserRoleCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Nombre de usuario')
            ->addArgument('role',     InputArgument::REQUIRED, 'Rol a asignar (ej: ROLE_DEVELOPER)')
            ->addArgument('action',   InputArgument::OPTIONAL, 'add o remove', 'add');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $role     = strtoupper($input->getArgument('role'));
        $action   = $input->getArgument('action');

        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            $io->error("Usuario '$username' no encontrado.");
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        $roles = array_filter($roles, fn($r) => $r !== 'ROLE_USER'); // getRoles() siempre añade ROLE_USER

        if ($action === 'add') {
            if (in_array($role, $roles)) {
                $io->warning("El usuario '$username' ya tiene el rol '$role'.");
                return Command::SUCCESS;
            }
            $roles[] = $role;
            $user->setRoles(array_values($roles));
            $this->em->flush();
            $io->success("Rol '$role' asignado a '$username'.");
        } elseif ($action === 'remove') {
            $roles = array_filter($roles, fn($r) => $r !== $role);
            $user->setRoles(array_values($roles));
            $this->em->flush();
            $io->success("Rol '$role' revocado de '$username'.");
        } else {
            $io->error("Acción inválida. Usa 'add' o 'remove'.");
            return Command::FAILURE;
        }

        $io->table(['Usuario', 'Roles'], [[$username, implode(', ', $user->getRoles())]]);

        return Command::SUCCESS;
    }
}
