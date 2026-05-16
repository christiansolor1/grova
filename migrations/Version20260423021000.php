<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423021000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UI icon catalog menu item under UI components';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role)
            SELECT 'ui-icons-catalog', 'ui-kit', 'Iconos BI/FA', 'bi-stars', 38, 1, 0, NULL
            WHERE NOT EXISTS (
                SELECT 1 FROM menu WHERE menu_key = 'ui-icons-catalog'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'ui-icons-catalog'");
    }
}
