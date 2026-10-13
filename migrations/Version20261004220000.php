<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega hora, lugar y url a agenda_evento';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agenda_evento ADD hora VARCHAR(5) DEFAULT NULL AFTER parentesco, ADD lugar VARCHAR(200) DEFAULT NULL AFTER hora, ADD url VARCHAR(300) DEFAULT NULL AFTER lugar');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agenda_evento DROP hora, DROP lugar, DROP url');
    }
}
