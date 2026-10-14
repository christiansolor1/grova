<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Work: menu item en grova_core.
 * Las tablas work_* se crean en migraciones posteriores con tenant_id.
 */
final class Version20260509030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work module: menu item in grova_core';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, label, icon, parent_key, sort_order, show_in_sidebar, dev_only, status)
            VALUES ('work', 'Work', 'bi bi-briefcase', NULL, 20, 1, 0, 'activo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'work'");
    }
}
