<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ui-tables-wide menu item under UI components';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, enabled, dev_only, required_role)
            SELECT 'ui-tables-wide', 'ui-showcase', 'Tablas (filtros anchos)', 'bi-layout-sidebar', 36, 1, 0, NULL
            WHERE NOT EXISTS (
                SELECT 1 FROM menu WHERE menu_key = 'ui-tables-wide'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'ui-tables-wide'");
    }
}
