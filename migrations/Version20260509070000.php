<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Construcción: menu item en grova_core.
 * Las tablas construccion_* se crean en migraciones posteriores con tenant_id.
 */
final class Version20260509070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Construccion module: menu item in grova_core';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'construccion', NULL, 'Construcción', 'bi-building', 60, 1, 0, NULL, 'hecho'
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'construccion')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'construccion'");
    }
}
