<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega wallet_cuenta_id a work_client (qué cuenta de Wallet recibe el pago de ese cliente)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_client ADD COLUMN wallet_cuenta_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE work_client ADD CONSTRAINT FK_work_client_wallet_cuenta FOREIGN KEY (wallet_cuenta_id) REFERENCES wallet_cuenta(id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_client DROP FOREIGN KEY FK_work_client_wallet_cuenta');
        $this->addSql('ALTER TABLE work_client DROP COLUMN wallet_cuenta_id');
    }
}
