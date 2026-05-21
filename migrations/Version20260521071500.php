<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Asegura que las columnas de perfil de User existen.
 * La migración 064650 pudo haber fallado antes de llegar a este ALTER en algunos entornos.
 */
final class Version20260521071500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Columnas de perfil en user (IF NOT EXISTS) — reparación de migración 064650 parcialmente fallida';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE user
                ADD COLUMN IF NOT EXISTS avatar_url      VARCHAR(500) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS telefono        VARCHAR(20)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS fecha_nacimiento DATETIME    DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS genero          VARCHAR(20)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS pais            VARCHAR(100) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS ciudad          VARCHAR(100) DEFAULT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user
            DROP COLUMN IF EXISTS avatar_url,
            DROP COLUMN IF EXISTS telefono,
            DROP COLUMN IF EXISTS fecha_nacimiento,
            DROP COLUMN IF EXISTS genero,
            DROP COLUMN IF EXISTS pais,
            DROP COLUMN IF EXISTS ciudad
        ');
    }
}
