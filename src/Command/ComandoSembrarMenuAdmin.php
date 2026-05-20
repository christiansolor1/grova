<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Menu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('grova:sembrar-menu-admin', description: 'Crea/actualiza ítems de menú del panel de administración en el sidebar.')]
final class ComandoSembrarMenuAdmin extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(Menu::class);

        // ── Gestión de Usuarios (sección) ────────────────────────────────────
        $this->getOrCreateMenu($repo, 'gestion-usuarios-section', [
            'parentKey' => null,
            'label' => 'Gestión de Usuarios',
            'icon' => 'bi-people',
            'sortOrder' => 70,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_ADMIN',
            'status' => 'hecho',
        ], $output);

        // Organizaciones (antes "Tenants")
        $this->getOrCreateMenu($repo, 'tenants', [
            'parentKey' => 'gestion-usuarios-section',
            'label' => 'Organizaciones',
            'icon' => 'bi-globe',
            'sortOrder' => 5,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_SUPER_ADMIN',
            'status' => 'hecho',
        ], $output);

        // Usuarios
        $this->getOrCreateMenu($repo, 'usuarios', [
            'parentKey' => 'gestion-usuarios-section',
            'label' => 'Usuarios',
            'icon' => 'bi-person-badge',
            'sortOrder' => 10,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_ADMIN',
            'status' => 'hecho',
        ], $output);

        // Clientes (pendiente)
        $this->getOrCreateMenu($repo, 'clientes', [
            'parentKey' => 'gestion-usuarios-section',
            'label' => 'Clientes',
            'icon' => 'bi-people',
            'sortOrder' => 20,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_ADMIN',
            'status' => 'pendiente',
        ], $output);

        // Subclientes (pendiente)
        $this->getOrCreateMenu($repo, 'subclientes', [
            'parentKey' => 'gestion-usuarios-section',
            'label' => 'Subclientes',
            'icon' => 'bi-layers',
            'sortOrder' => 30,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_ADMIN',
            'status' => 'pendiente',
        ], $output);

        // Proveedores (pendiente)
        $this->getOrCreateMenu($repo, 'proveedores', [
            'parentKey' => 'gestion-usuarios-section',
            'label' => 'Proveedores',
            'icon' => 'bi-truck',
            'sortOrder' => 40,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_ADMIN',
            'status' => 'pendiente',
        ], $output);

        // Sucursales (pendiente)
        $this->getOrCreateMenu($repo, 'sucursales', [
            'parentKey' => 'gestion-usuarios-section',
            'label' => 'Sucursales',
            'icon' => 'bi-geo-alt',
            'sortOrder' => 50,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_ADMIN',
            'status' => 'pendiente',
        ], $output);

        // ── Administración (sección) ──────────────────────────────────────────
        $this->getOrCreateMenu($repo, 'admin-section', [
            'parentKey' => null,
            'label' => 'Administración',
            'icon' => 'bi-gear',
            'sortOrder' => 80,
            'showInSidebar' => true,
            'requiredRole' => 'ROLE_ADMIN',
            'status' => 'hecho',
        ], $output);

        // Respaldos
        $this->getOrCreateMenu($repo, 'respaldo', [
            'parentKey' => 'admin-section',
            'label' => 'Respaldos',
            'icon' => 'bi-archive',
            'sortOrder' => 10,
            'showInSidebar' => true,
            'requiredRole' => null,
            'status' => 'hecho',
        ], $output);

        $this->em->flush();

        $output->writeln('Menú actualizado: Gestión de Usuarios > Organizaciones, Usuarios, Clientes, Subclientes, Proveedores, Sucursales | Administración > Respaldos.');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $props
     */
    private function getOrCreateMenu(mixed $repo, string $menuKey, array $props, OutputInterface $output): Menu
    {
        $menu = $repo->findOneBy(['menuKey' => $menuKey]);

        if ($menu === null) {
            $menu = new Menu();
            $menu->setMenuKey($menuKey);
            foreach ($props as $method => $value) {
                $setter = 'set'.ucfirst($method);
                $menu->$setter($value);
            }
            $this->em->persist($menu);
            $output->writeln("  + {$props['label']} ({$menuKey})");
        } else {
            $changed = false;
            foreach ($props as $method => $value) {
                $setter = 'set'.ucfirst($method);
                $getter = \in_array($method, ['showInSidebar', 'devOnly'], true)
                    ? 'is'.ucfirst($method)
                    : 'get'.ucfirst($method);
                if ($menu->$getter() !== $value) {
                    $menu->$setter($value);
                    $changed = true;
                }
            }
            if ($changed) {
                $output->writeln("  ~ {$props['label']} ({$menuKey}) actualizado");
            }
        }

        return $menu;
    }
}
