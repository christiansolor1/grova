<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type and dismissed_at to notification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE notification ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'info'");
        $this->addSql('ALTER TABLE notification ADD COLUMN dismissed_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_notification_user_dismissed ON notification (user_id, dismissed_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_notification_user_dismissed ON notification');
        $this->addSql('ALTER TABLE notification DROP COLUMN dismissed_at');
        $this->addSql('ALTER TABLE notification DROP COLUMN type');
    }
}
