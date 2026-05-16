<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UI component pages: auth, user profile, permissions, data display, settings, admin, API reference';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-auth', 'ui-showcase', 'Auth', 'bi-shield-lock', 61, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-auth')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-user-profile', 'ui-showcase', 'Perfil de usuario', 'bi-person-circle', 62, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-user-profile')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-permissions', 'ui-showcase', 'Permisos y roles', 'bi-person-check', 63, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-permissions')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-data-display', 'ui-showcase', 'Visualización de datos', 'bi-bar-chart-line', 64, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-data-display')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-settings', 'ui-showcase', 'Configuración', 'bi-gear', 65, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-settings')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-admin', 'ui-showcase', 'Admin / Super-admin', 'bi-buildings', 66, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-admin')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-api-reference', 'ui-showcase', 'API Reference', 'bi-code-slash', 67, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-api-reference')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key IN ('ui-auth','ui-user-profile','ui-permissions','ui-data-display','ui-settings','ui-admin','ui-api-reference')");
    }
}
