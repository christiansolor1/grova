<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega apodo y parentesco a agenda_evento';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agenda_evento ADD apodo VARCHAR(100) DEFAULT NULL AFTER nombre, ADD parentesco VARCHAR(50) DEFAULT NULL AFTER apodo');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agenda_evento DROP apodo, DROP parentesco');
    }
}
