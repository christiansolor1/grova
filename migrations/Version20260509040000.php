<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Pesca: menu item en grova_core.
 * Las tablas fishing_* se crean en migraciones posteriores con tenant_id.
 */
final class Version20260509040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fishing module: menu item in grova_core';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, label, icon, parent_key, sort_order, show_in_sidebar, dev_only, status)
            VALUES ('pesca', 'Pesca', 'bi bi-water', NULL, 30, 1, 0, 'activo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'pesca'");
    }
}
