<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Legal: menu items en grova_core.
 * Las tablas contact y legal_* se crean en migraciones posteriores con tenant_id.
 */
final class Version20260509050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Legal module: menu items in grova_core';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, label, icon, parent_key, sort_order, show_in_sidebar, dev_only, status)
            VALUES
                ('contactos', 'Contactos', 'bi bi-people',     NULL, 40, 1, 0, 'activo'),
                ('legal',     'Legal',     'bi bi-briefcase2', NULL, 50, 1, 0, 'activo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key IN ('contactos','legal')");
    }
}
