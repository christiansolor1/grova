<?php

declare(strict_types=1);

namespace App\Workspace;

/**
 * Hojas del menú (BD) → nombre de ruta Symfony. Una sola ruta por pantalla (mismo path en todos los entornos).
 */
final class WorkspaceLeafRoutes
{
    /** @var array<string, string> menu_key => route name */
    public const ROUTE_BY_MENU_KEY = [
        'dashboard'            => 'grova_page_dashboard',
        'users-users'          => 'grova_page_users_users',
        'users-access-control' => 'grova_page_users_access_control',
        'ui-buttons'           => 'grova_page_ui_buttons',
        'ui-inputs'            => 'grova_page_ui_inputs',
        'ui-alerts-swal'       => 'grova_page_ui_alerts_swal',
        'ui-tables'            => 'grova_page_ui_tables',
        'ui-status'            => 'grova_page_ui_status',
        'menu-manager'         => 'grova_page_menu_manager',
    ];

    /** @var array<string, string> route name => menu_key */
    private const MENU_KEY_BY_ROUTE = [
        'grova_page_dashboard'            => 'dashboard',
        'grova_page_users_users'          => 'users-users',
        'grova_page_users_access_control'   => 'users-access-control',
        'grova_page_ui_buttons'             => 'ui-buttons',
        'grova_page_ui_inputs'              => 'ui-inputs',
        'grova_page_ui_alerts_swal'        => 'ui-alerts-swal',
        'grova_page_ui_tables'              => 'ui-tables',
        'grova_page_ui_status'              => 'ui-status',
        'grova_page_menu_manager'           => 'menu-manager',
    ];

    public static function routeNameForMenuKey(string $menuKey): string
    {
        if (!isset(self::ROUTE_BY_MENU_KEY[$menuKey])) {
            throw new \InvalidArgumentException('Menú hoja sin ruta: '.$menuKey);
        }

        return self::ROUTE_BY_MENU_KEY[$menuKey];
    }

    public static function menuKeyForRouteName(string $routeName): ?string
    {
        return self::MENU_KEY_BY_ROUTE[$routeName] ?? null;
    }

    /** @return list<string> */
    public static function menuKeys(): array
    {
        return array_keys(self::ROUTE_BY_MENU_KEY);
    }
}
