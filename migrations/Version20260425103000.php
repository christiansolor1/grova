<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reorganize menu tree and add config menu builder entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'config', NULL, 'Configuración', 'bi-gear', 90, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (
                SELECT 1 FROM menu WHERE menu_key = 'config'
            )
        ");

        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'config-menu', 'config', 'Menú', 'bi-list', 91, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (
                SELECT 1 FROM menu WHERE menu_key = 'config-menu'
            )
        ");

        $this->addSql("
            UPDATE menu
            SET parent_key = 'config-menu',
                sort_order = 92,
                label = 'Gestión de menú'
            WHERE menu_key = 'users-menu-governance'
        ");

        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'config-menu-builder', 'config-menu', 'Menu Builder', 'bi-hammer', 93, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (
                SELECT 1 FROM menu WHERE menu_key = 'config-menu-builder'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'config-menu-builder'");
        $this->addSql("DELETE FROM menu WHERE menu_key = 'config-menu'");
        $this->addSql("DELETE FROM menu WHERE menu_key = 'config'");

        $this->addSql("
            UPDATE menu
            SET parent_key = 'users',
                sort_order = 23,
                label = 'Gestión de menú'
            WHERE menu_key = 'users-menu-governance'
        ");
    }
}
