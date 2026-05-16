<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422204500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename sidebar_menu_item to menu_item (standard menu table)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE sidebar_menu_item TO menu_item');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE menu_item TO sidebar_menu_item');
    }
}
