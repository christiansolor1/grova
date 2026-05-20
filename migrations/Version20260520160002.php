<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520160002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE error_log (id INT AUTO_INCREMENT NOT NULL, level VARCHAR(50) NOT NULL, channel VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, context JSON DEFAULT NULL, extra JSON DEFAULT NULL, trace LONGTEXT DEFAULT NULL, file VARCHAR(500) DEFAULT NULL, line INT DEFAULT NULL, exception_class VARCHAR(255) DEFAULT NULL, tenant_id INT DEFAULT NULL, user_id INT DEFAULT NULL, url VARCHAR(1000) DEFAULT NULL, status VARCHAR(20) DEFAULT \'new\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_error_log_level (level), INDEX idx_error_log_status (status), INDEX idx_error_log_created (created_at), INDEX idx_error_log_tenant (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE error_log');
    }
}
