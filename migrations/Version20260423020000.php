<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize menu icons to Bootstrap or Font Awesome formats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE menu
            SET icon = CASE LOWER(TRIM(icon))
                WHEN 'dashboard' THEN 'bi-grid'
                WHEN 'users' THEN 'bi-people'
                WHEN 'list' THEN 'bi-list'
                WHEN 'shield' THEN 'bi-shield'
                WHEN 'plug' THEN 'bi-plug'
                WHEN 'check' THEN 'bi-check2'
                WHEN 'edit' THEN 'bi-pencil'
                WHEN 'alert' THEN 'bi-exclamation-triangle'
                WHEN 'settings' THEN 'bi-gear'
                WHEN 'layout' THEN 'bi-layout-sidebar'
                WHEN 'user' THEN 'bi-person'
                WHEN 'dot' THEN 'bi-dot'
                WHEN 'folder' THEN 'bi-folder'
                ELSE icon
            END
            WHERE icon IS NOT NULL AND icon <> ''
        ");

        $this->addSql("
            UPDATE menu
            SET icon = CONCAT('bi-', LOWER(TRIM(icon)))
            WHERE icon IS NOT NULL
              AND icon <> ''
              AND LOWER(TRIM(icon)) NOT LIKE 'bi-%'
              AND LOWER(TRIM(icon)) NOT LIKE 'fa-%'
              AND LOWER(TRIM(icon)) NOT LIKE 'fa %'
              AND LOWER(TRIM(icon)) REGEXP '^[a-z0-9-]+$'
        ");
    }

    public function down(Schema $schema): void
    {
        // No-op: los datos se normalizan a un formato canónico.
    }
}
