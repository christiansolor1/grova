<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ui_badge and ui_style to menu';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE menu ADD ui_badge VARCHAR(32) DEFAULT NULL, ADD ui_style LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE menu DROP ui_badge, DROP ui_style');
    }
}

