<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Menu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'grova:sembrar-menu-base',
    description: 'Crea/actualiza todos los ítems de menú del sidebar (módulos, admin, utilería).',
)]
final class ComandoSembrarMenuBase extends Command
{
    /**
     * @var list<array{key:string, label:string, icon:string, sort:int, role?:string, status?:string, parent?:string, devOnly?:bool}>
     */
    private const ITEMS = [
        // ── Raíces siempre visibles ──────────────────────────────────────────
        ['key' => 'dashboard',             'label' => 'Inicio',           'icon' => 'bi-house',          'sort' => 10],
        ['key' => 'notifications-history', 'label' => 'Notificaciones',   'icon' => 'bi-bell',           'sort' => 20],

        // ── Módulos (se agrupan bajo "modulos-section") ──────────────────────
        ['key' => 'modulos-section',       'label' => 'Módulos',          'icon' => 'bi-grid',           'sort' => 100],
        ['key' => 'wallet',                'label' => 'Wallet',           'icon' => 'bi-wallet2',        'sort' => 10,  'parent' => 'modulos-section'],
        ['key' => 'work',                  'label' => 'Work',             'icon' => 'bi-briefcase',      'sort' => 20,  'parent' => 'modulos-section'],
        ['key' => 'agenda',                'label' => 'Agenda',           'icon' => 'bi-calendar',       'sort' => 30,  'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'habitos',               'label' => 'Hábitos',          'icon' => 'bi-check-circle',   'sort' => 40,  'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'pesca',                 'label' => 'Pesca',            'icon' => 'bi-water',          'sort' => 50,  'parent' => 'modulos-section'],
        ['key' => 'contactos',             'label' => 'Contactos',        'icon' => 'bi-people',         'sort' => 60,  'parent' => 'modulos-section'],
        ['key' => 'legal',                 'label' => 'Legal',            'icon' => 'bi-gavel',          'sort' => 70,  'parent' => 'modulos-section'],
        ['key' => 'construccion',          'label' => 'Construcción',     'icon' => 'bi-building',       'sort' => 80,  'parent' => 'modulos-section'],
        ['key' => 'facturacion',           'label' => 'Facturación',      'icon' => 'bi-receipt',        'sort' => 90,  'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'rrhh',                  'label' => 'RRHH',             'icon' => 'bi-person-badge',   'sort' => 100, 'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'inventario',            'label' => 'Inventario',       'icon' => 'bi-box',            'sort' => 110, 'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'pos',                   'label' => 'POS',              'icon' => 'bi-shop',           'sort' => 120, 'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'restaurante',           'label' => 'Restaurante',      'icon' => 'bi-cup-hot',        'sort' => 130, 'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'clinica',               'label' => 'Clínica',          'icon' => 'bi-heart-pulse',    'sort' => 140, 'parent' => 'modulos-section', 'status' => 'pendiente'],
        ['key' => 'financiera',            'label' => 'Financiera',       'icon' => 'bi-bank',           'sort' => 150, 'parent' => 'modulos-section', 'status' => 'pendiente'],

        // ── Administración (ROLE_ADMIN+) ─────────────────────────────────────
        ['key' => 'admin-section',         'label' => 'Administración',   'icon' => 'bi-gear',           'sort' => 900, 'role' => 'ROLE_ADMIN'],
        ['key' => 'usuarios',              'label' => 'Usuarios',         'icon' => 'bi-person-badge',   'sort' => 10,  'parent' => 'admin-section', 'role' => 'ROLE_ADMIN'],
        ['key' => 'tenants',               'label' => 'Organizaciones',   'icon' => 'bi-globe',          'sort' => 5,   'parent' => 'admin-section', 'role' => 'ROLE_SUPER_ADMIN'],
        ['key' => 'clientes',              'label' => 'Clientes',         'icon' => 'bi-people',         'sort' => 20,  'parent' => 'admin-section', 'role' => 'ROLE_ADMIN', 'status' => 'pendiente'],
        ['key' => 'subclientes',           'label' => 'Subclientes',      'icon' => 'bi-layers',         'sort' => 30,  'parent' => 'admin-section', 'role' => 'ROLE_ADMIN', 'status' => 'pendiente'],
        ['key' => 'proveedores',           'label' => 'Proveedores',      'icon' => 'bi-truck',          'sort' => 40,  'parent' => 'admin-section', 'role' => 'ROLE_ADMIN', 'status' => 'pendiente'],
        ['key' => 'sucursales',            'label' => 'Sucursales',       'icon' => 'bi-geo-alt',        'sort' => 50,  'parent' => 'admin-section', 'role' => 'ROLE_ADMIN', 'status' => 'pendiente'],
        ['key' => 'respaldo',              'label' => 'Respaldos',        'icon' => 'bi-archive',        'sort' => 60,  'parent' => 'admin-section'],

        // ── Desarrollo (devOnly) ─────────────────────────────────────────────
        ['key' => 'dev-section',           'label' => 'Desarrollo',       'icon' => 'bi-code-square',    'sort' => 950, 'devOnly' => true],
        ['key' => 'ui-buttons',            'label' => 'Botones',          'icon' => 'bi-square',         'sort' => 10,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-inputs',             'label' => 'Inputs',           'icon' => 'bi-input-cursor',   'sort' => 20,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-alerts-swal-catalog', 'label' => 'Alertas (Swal)',  'icon' => 'bi-exclamation-triangle', 'sort' => 25, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-tables',             'label' => 'Tablas',           'icon' => 'bi-table',          'sort' => 30,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-tables-wide',        'label' => 'Tablas (ancho)',   'icon' => 'bi-layout-three-columns', 'sort' => 35, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-status',             'label' => 'Estados',          'icon' => 'bi-check2-square',  'sort' => 40,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-icons-catalog',      'label' => 'Iconos',           'icon' => 'bi-emoji-smile',    'sort' => 45,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-modals',             'label' => 'Modales',          'icon' => 'bi-window',         'sort' => 50,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-panels',             'label' => 'Paneles',          'icon' => 'bi-layout-three-columns', 'sort' => 55, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-cards',              'label' => 'Tarjetas',         'icon' => 'bi-card-text',      'sort' => 60,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-toasts',             'label' => 'Toasts',           'icon' => 'bi-info-circle',    'sort' => 65,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-empty-states',       'label' => 'Estados vacíos',   'icon' => 'bi-inbox',          'sort' => 70,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-error-pages',        'label' => 'Páginas error',    'icon' => 'bi-exclamation-octagon', 'sort' => 75, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-page-headers',       'label' => 'Encabezados',      'icon' => 'bi-type-h1',        'sort' => 80,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-auth',               'label' => 'Auth',             'icon' => 'bi-shield-lock',    'sort' => 85,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-user-profile',       'label' => 'Perfil usuario',   'icon' => 'bi-person-circle',  'sort' => 90,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-permissions',        'label' => 'Permisos',         'icon' => 'bi-shield-check',   'sort' => 95,  'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-data-display',       'label' => 'Data display',     'icon' => 'bi-bar-chart',      'sort' => 100, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-settings',           'label' => 'Settings',         'icon' => 'bi-sliders',        'sort' => 105, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-admin',              'label' => 'Admin panel',      'icon' => 'bi-shield',         'sort' => 110, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-api-reference',      'label' => 'API Reference',    'icon' => 'bi-code',           'sort' => 115, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'ui-loading-states',     'label' => 'Loading states',   'icon' => 'bi-arrow-repeat',   'sort' => 120, 'parent' => 'dev-section', 'devOnly' => true],
        ['key' => 'menu-manager',          'label' => 'Gestor de menú',   'icon' => 'bi-list-ul',        'sort' => 125, 'parent' => 'dev-section', 'devOnly' => true],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(Menu::class);

        foreach (self::ITEMS as $def) {
            $menu = $repo->findOneBy(['menuKey' => $def['key']]);

            if ($menu === null) {
                $menu = new Menu();
                $menu->setMenuKey($def['key']);
            }

            $menu->setLabel($def['label']);
            $menu->setIcon($def['icon']);
            $menu->setSortOrder($def['sort']);
            $menu->setParentKey($def['parent'] ?? null);
            $menu->setRequiredRole($def['role'] ?? null);
            $menu->setStatus($def['status'] ?? 'hecho');
            $menu->setDevOnly($def['devOnly'] ?? false);
            $menu->setShowInSidebar(true);

            if ($menu->getId() === null) {
                $this->em->persist($menu);
                $output->writeln("  + {$def['label']} ({$def['key']})");
            }
        }

        $this->em->flush();
        $output->writeln(sprintf("\n✓ %d ítems de menú sincronizados.", count(self::ITEMS)));

        return Command::SUCCESS;
    }
}
