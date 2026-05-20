<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518224528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD totp_secret VARCHAR(64) DEFAULT NULL, CHANGE email_verificado email_verificado TINYINT NOT NULL, CHANGE token_verifica_expira token_verifica_expira DATETIME DEFAULT NULL, CHANGE reset_token_expira reset_token_expira DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP totp_secret, CHANGE email_verificado email_verificado TINYINT DEFAULT 0 NOT NULL, CHANGE token_verifica_expira token_verifica_expira DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE reset_token_expira reset_token_expira DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
