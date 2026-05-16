<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260511143929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_lock (id INT AUTO_INCREMENT NOT NULL, pin_hash VARCHAR(255) DEFAULT NULL, locked_sections JSON NOT NULL, unlock_ttl_minutes INT NOT NULL, webauthn_credential_id LONGTEXT DEFAULT NULL, webauthn_public_key LONGTEXT DEFAULT NULL, webauthn_enabled TINYINT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_FD0ED7C7A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_lock ADD CONSTRAINT FK_FD0ED7C7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_lock DROP FOREIGN KEY FK_FD0ED7C7A76ED395');
        $this->addSql('DROP TABLE user_lock');
    }
}
