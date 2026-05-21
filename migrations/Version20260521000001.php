<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ampliar notification.tenant_slug de 20 a 60 chars para soportar slugs largos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification CHANGE tenant_slug tenant_slug VARCHAR(60) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification CHANGE tenant_slug tenant_slug VARCHAR(20) DEFAULT NULL');
    }
}
