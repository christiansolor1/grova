<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519131844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE consentimiento_biometrico (
              id INT AUTO_INCREMENT NOT NULL,
              accepted_at DATETIME NOT NULL,
              ip VARCHAR(15) NOT NULL,
              user_agent VARCHAR(512) DEFAULT NULL,
              version VARCHAR(10) DEFAULT '1.0' NOT NULL,
              usuario_id INT NOT NULL,
              INDEX IDX_7D09DA6FDB38439E (usuario_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              consentimiento_biometrico
            ADD
              CONSTRAINT FK_7D09DA6FDB38439E FOREIGN KEY (usuario_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consentimiento_biometrico DROP FOREIGN KEY FK_7D09DA6FDB38439E');
        $this->addSql('DROP TABLE consentimiento_biometrico');
    }
}
