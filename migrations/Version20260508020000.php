<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Wallet: menu item en grova_core.
 * Las tablas wallet_* se crean en migraciones posteriores con tenant_id.
 */
final class Version20260508020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wallet module: menu item in grova_core';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'wallet', NULL, 'Wallet', 'bi-wallet2', 10, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'wallet')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'wallet'");
    }
}
