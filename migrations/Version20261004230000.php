<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cambia dias_anticipacion (int) por avisos (json array) en agenda_evento';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agenda_evento ADD avisos LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\' AFTER dias_anticipacion');
        $this->addSql('UPDATE agenda_evento SET avisos = JSON_ARRAY(dias_anticipacion)');
        $this->addSql('ALTER TABLE agenda_evento DROP dias_anticipacion');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agenda_evento ADD dias_anticipacion INT NOT NULL DEFAULT 1 AFTER notificar_push');
        $this->addSql('UPDATE agenda_evento SET dias_anticipacion = JSON_UNQUOTE(JSON_EXTRACT(avisos, \'$[0]\'))');
        $this->addSql('ALTER TABLE agenda_evento DROP avisos');
    }
}
