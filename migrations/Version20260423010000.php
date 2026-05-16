<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename menu.enabled to show_in_sidebar for clearer semantics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE menu CHANGE enabled show_in_sidebar TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE menu CHANGE show_in_sidebar enabled TINYINT(1) NOT NULL');
    }
}
