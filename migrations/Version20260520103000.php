<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Catálogo UI: pantalla de referencia para spinners, overlays, CRUD y DataTables (processing).
 */
final class Version20260520103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Menu: ui-loading-states bajo Catálogo UI (ui-showcase)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-loading-states', 'ui-showcase', 'Cargas y esperas', 'bi-arrow-repeat', 68, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-loading-states')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'ui-loading-states'");
    }
}
