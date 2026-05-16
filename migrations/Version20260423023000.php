<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sample pending menu item under UI kit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-pending-demo', 'ui-kit', 'Demo pendiente', 'bi-hourglass-split', 39, 1, 0, NULL, 'pendiente'
            WHERE NOT EXISTS (
                SELECT 1 FROM menu WHERE menu_key = 'ui-pending-demo'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'ui-pending-demo'");
    }
}
