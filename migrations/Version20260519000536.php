<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519000536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD totp2fa_enabled TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('UPDATE user SET totp2fa_enabled = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP totp2fa_enabled');
    }
}
