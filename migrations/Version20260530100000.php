<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Asegura el ítem de menú "Cargas y esperas" (ui-loading-states) bajo Catálogo UI.
 * Idempotente: no duplica si ya existe (p. ej. tras Version20260520103000).
 */
final class Version20260530100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-seed menu ui-loading-states bajo ui-showcase si no existe';
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
        // No borrar: el ítem puede haberse creado en migración anterior; el down deja el menú intacto.
    }
}
