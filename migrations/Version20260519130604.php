<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519130604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user_codigo_2fa_email (
              id INT AUTO_INCREMENT NOT NULL,
              codigo VARCHAR(6) NOT NULL,
              expires_at DATETIME NOT NULL,
              usado TINYINT DEFAULT 0 NOT NULL,
              created_at DATETIME NOT NULL,
              usuario_id INT NOT NULL,
              INDEX IDX_BB956BC9DB38439E (usuario_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_codigo_2fa_email
            ADD
              CONSTRAINT FK_BB956BC9DB38439E FOREIGN KEY (usuario_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE user ADD email2fa_enabled TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_codigo_2fa_email DROP FOREIGN KEY FK_BB956BC9DB38439E');
        $this->addSql('DROP TABLE user_codigo_2fa_email');
        $this->addSql('ALTER TABLE user DROP email2fa_enabled');
    }
}
