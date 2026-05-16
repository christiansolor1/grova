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
        'dashboard'               => 'grova_page_dashboard',
        'notifications-history'   => 'notifications_history',
        'wallet'                  => 'grova_wallet_index',
        'work'                 => 'grova_work_index',
        'pesca'                => 'grova_fishing_index',
        'contactos'            => 'grova_contacts_index',
        'legal'                => 'grova_legal_index',
        'construccion'         => 'grova_construccion_index',
        'users-users'          => 'grova_page_users_users',
        'users-access-control' => 'grova_page_users_access_control',
        'users-menu-governance' => 'grova_page_users_menu_governance',
        'config-menu-builder'  => 'grova_page_config_menu_builder',
        'ui-buttons'           => 'grova_page_ui_buttons',
        'ui-inputs'            => 'grova_page_ui_inputs',
        'ui-alerts-swal-catalog'              => 'grova_page_ui_alerts_swal',
        'ui-alerts-swal-select-multi-native'  => 'grova_page_ui_alerts_swal_select_multi_native',
        'ui-alerts-swal-select2-multi'        => 'grova_page_ui_alerts_swal_select2_multi',
        'ui-alerts-swal-select-multi-tags'    => 'grova_page_ui_alerts_swal_select_multi_tags',
        'ui-tables'            => 'grova_page_ui_tables',
        'ui-tables-wide'       => 'grova_page_ui_tables_wide',
        'ui-status'            => 'grova_page_ui_status',
        'ui-icons-catalog'     => 'grova_page_ui_icons_catalog',
        'ui-modals'            => 'grova_page_ui_modals',
        'ui-panels'            => 'grova_page_ui_panels',
        'ui-cards'             => 'grova_page_ui_cards',
        'ui-toasts'            => 'grova_page_ui_toasts',
        'ui-empty-states'      => 'grova_page_ui_empty_states',
        'ui-error-pages'       => 'grova_page_ui_error_pages',
        'ui-page-headers'      => 'grova_page_ui_page_headers',
        'ui-auth'              => 'grova_page_ui_auth',
        'ui-user-profile'      => 'grova_page_ui_user_profile',
        'ui-permissions'       => 'grova_page_ui_permissions',
        'ui-data-display'      => 'grova_page_ui_data_display',
        'ui-settings'          => 'grova_page_ui_settings',
        'ui-admin'             => 'grova_page_ui_admin',
        'ui-api-reference'     => 'grova_page_ui_api_reference',
        'ui-loading-states'    => 'grova_page_ui_loading_states',
        'menu-manager'         => 'grova_page_menu_manager',
    ];

    /** @var array<string, string> route name => menu_key */
    private const MENU_KEY_BY_ROUTE = [
        'grova_page_dashboard'            => 'dashboard',
        'notifications_history'           => 'notifications-history',
        'grova_wallet_index'              => 'wallet',
        'grova_work_index'                => 'work',
        'grova_fishing_index'             => 'pesca',
        'grova_contacts_index'            => 'contactos',
        'grova_legal_index'               => 'legal',
        'grova_construccion_index'        => 'construccion',
        'grova_page_users_users'          => 'users-users',
        'grova_page_users_access_control'   => 'users-access-control',
        'grova_page_users_menu_governance'  => 'users-menu-governance',
        'grova_page_config_menu_builder'    => 'config-menu-builder',
        'grova_page_ui_buttons'             => 'ui-buttons',
        'grova_page_ui_inputs'              => 'ui-inputs',
        'grova_page_ui_alerts_swal'                     => 'ui-alerts-swal-catalog',
        'grova_page_ui_alerts_swal_select_multi_native' => 'ui-alerts-swal-select-multi-native',
        'grova_page_ui_alerts_swal_select2_multi'       => 'ui-alerts-swal-select2-multi',
        'grova_page_ui_alerts_swal_select_multi_tags'   => 'ui-alerts-swal-select-multi-tags',
        'grova_page_ui_tables'              => 'ui-tables',
        'grova_page_ui_tables_wide'         => 'ui-tables-wide',
        'grova_page_ui_status'              => 'ui-status',
        'grova_page_ui_icons_catalog'       => 'ui-icons-catalog',
        'grova_page_ui_modals'              => 'ui-modals',
        'grova_page_ui_panels'              => 'ui-panels',
        'grova_page_ui_cards'              => 'ui-cards',
        'grova_page_ui_toasts'             => 'ui-toasts',
        'grova_page_ui_empty_states'       => 'ui-empty-states',
        'grova_page_ui_error_pages'        => 'ui-error-pages',
        'grova_page_ui_page_headers'       => 'ui-page-headers',
        'grova_page_ui_auth'               => 'ui-auth',
        'grova_page_ui_user_profile'       => 'ui-user-profile',
        'grova_page_ui_permissions'        => 'ui-permissions',
        'grova_page_ui_data_display'       => 'ui-data-display',
        'grova_page_ui_settings'           => 'ui-settings',
        'grova_page_ui_admin'              => 'ui-admin',
        'grova_page_ui_api_reference'      => 'ui-api-reference',
        'grova_page_ui_loading_states'     => 'ui-loading-states',
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
