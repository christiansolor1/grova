<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                user_id     INT NOT NULL,
                tenant_slug VARCHAR(20)  DEFAULT NULL,
                title       VARCHAR(255) NOT NULL,
                body        TEXT         NOT NULL,
                icon        VARCHAR(100) DEFAULT NULL,
                url         VARCHAR(500) DEFAULT NULL,
                module      VARCHAR(50)  DEFAULT NULL,
                read_at     DATETIME     DEFAULT NULL,
                created_at  DATETIME     NOT NULL,
                context     JSON         DEFAULT NULL,
                CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->addSql('CREATE INDEX idx_notification_user_read ON notification (user_id, read_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
    }
}
