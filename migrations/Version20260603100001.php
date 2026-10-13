<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'wallet_entry: agrega cuenta_id FK a wallet_cuenta';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_entry
            ADD COLUMN cuenta_id INT DEFAULT NULL,
            ADD CONSTRAINT fk_wallet_entry_cuenta
                FOREIGN KEY (cuenta_id) REFERENCES wallet_cuenta (id) ON DELETE SET NULL
        ');
        $this->addSql('CREATE INDEX idx_wallet_entry_cuenta ON wallet_entry (cuenta_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_entry DROP FOREIGN KEY fk_wallet_entry_cuenta');
        $this->addSql('DROP INDEX idx_wallet_entry_cuenta ON wallet_entry');
        $this->addSql('ALTER TABLE wallet_entry DROP COLUMN cuenta_id');
    }
}
