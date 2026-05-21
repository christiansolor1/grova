<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permite que múltiples tenants compartan el mismo db_name (BD compartida en prod)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant DROP INDEX UNIQ_4E59C462628DE0D9');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E59C462628DE0D9 ON tenant (db_name)');
    }
}
