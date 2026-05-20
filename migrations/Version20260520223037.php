<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520223037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna lempiras_recibidos a work_invoice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_invoice ADD lempiras_recibidos NUMERIC(12, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_invoice DROP lempiras_recibidos');
    }
}
