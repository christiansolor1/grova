<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'work_invoice: agrega wallet_entry_id para sincronización con Wallet';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_invoice ADD COLUMN wallet_entry_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_invoice DROP COLUMN wallet_entry_id');
    }
}
