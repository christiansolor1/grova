<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sidebar menu items table and seed dashboard menu';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sidebar_menu_item (id INT AUTO_INCREMENT NOT NULL, menu_key VARCHAR(120) NOT NULL, parent_key VARCHAR(120) DEFAULT NULL, label VARCHAR(180) NOT NULL, icon VARCHAR(50) NOT NULL, sort_order INT NOT NULL, enabled TINYINT(1) NOT NULL, dev_only TINYINT(1) NOT NULL, required_role VARCHAR(120) DEFAULT NULL, UNIQUE INDEX uniq_sidebar_menu_key (menu_key), INDEX idx_sidebar_parent_key (parent_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("INSERT INTO sidebar_menu_item (menu_key, parent_key, label, icon, sort_order, enabled, dev_only, required_role) VALUES
            ('dashboard', NULL, 'Dashboard', 'dashboard', 10, 1, 0, NULL),
            ('users', NULL, 'Gestión de usuarios y accesos', 'users', 20, 1, 0, NULL),
            ('users-users', 'users', 'Usuarios', 'list', 21, 1, 0, NULL),
            ('users-access-control', 'users', 'Roles, permisos y control de acceso', 'shield', 22, 1, 0, NULL),
            ('ui-kit', NULL, 'Componentes UI', 'plug', 30, 1, 0, NULL),
            ('ui-showcase', 'ui-kit', 'Catalogo UI', 'list', 31, 1, 0, NULL),
            ('ui-buttons', 'ui-showcase', 'Botones', 'check', 32, 1, 0, NULL),
            ('ui-inputs', 'ui-showcase', 'Inputs y formularios', 'edit', 33, 1, 0, NULL),
            ('ui-alerts-swal', 'ui-showcase', 'Alertas SweetAlert2', 'alert', 34, 1, 0, NULL),
            ('ui-tables', 'ui-kit', 'Tablas y DataTables', 'dashboard', 35, 1, 0, NULL),
            ('ui-status', 'ui-kit', 'Badges y estados', 'shield', 36, 1, 0, NULL),
            ('menu-manager', 'ui-kit', 'Gestión de menús', 'settings', 37, 1, 1, NULL)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sidebar_menu_item');
    }
}
