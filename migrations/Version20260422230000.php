<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename menu_item table to menu';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE menu_item TO menu');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE menu TO menu_item');
    }
}
