<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: add pagada_at to work_invoice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("USE grova_christian");

        $this->addSql("ALTER TABLE work_invoice ADD COLUMN IF NOT EXISTS pagada_at DATETIME DEFAULT NULL AFTER enviada_at");

        // Dejar la conexión en grova para que Doctrine pueda leer doctrine_migration_versions
        $this->addSql("USE grova");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("USE grova_christian");

        $this->addSql("ALTER TABLE work_invoice DROP COLUMN IF EXISTS pagada_at");

        $this->addSql("USE grova");
    }
}

