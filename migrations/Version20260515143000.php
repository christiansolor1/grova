<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Menú Swal: catálogo bajo ui-alerts-swal + 3 demos de selección múltiple.
 */
final class Version20260515143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Menu: ui-alerts-swal hijos (catalogo + select multiple nativo, select2 multiple, etiquetas)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-alerts-swal-catalog', 'ui-alerts-swal', 'Catálogo SweetAlert2', 'bi-grid', 0, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-alerts-swal-catalog')
        ");

        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-alerts-swal-select-multi-native', 'ui-alerts-swal', 'Swal · Select múltiple nativo', 'bi-ui-checks-grid', 1, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-alerts-swal-select-multi-native')
        ");

        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-alerts-swal-select2-multi', 'ui-alerts-swal', 'Swal · Select2 múltiple', 'bi-list-check', 2, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-alerts-swal-select2-multi')
        ");

        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'ui-alerts-swal-select-multi-tags', 'ui-alerts-swal', 'Swal · Select múltiple etiquetas', 'bi-tags', 3, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'ui-alerts-swal-select-multi-tags')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key IN (
            'ui-alerts-swal-catalog',
            'ui-alerts-swal-select-multi-native',
            'ui-alerts-swal-select2-multi',
            'ui-alerts-swal-select-multi-tags'
        )");
    }
}
