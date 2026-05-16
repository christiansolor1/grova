<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register hidden profile menu item (not visible in sidebar)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, enabled, dev_only, required_role)
            SELECT 'profile-user', NULL, 'Perfil de usuario', 'bi-person', 999, 0, 0, NULL
            WHERE NOT EXISTS (
                SELECT 1 FROM menu WHERE menu_key = 'profile-user'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'profile-user'");
    }
}
