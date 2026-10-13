<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'wallet_cuenta: tabla de cuentas bancarias, cooperativa y otros medios';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE wallet_cuenta (
            id         INT AUTO_INCREMENT NOT NULL,
            tenant_id  INT NOT NULL,
            nombre     VARCHAR(100) NOT NULL,
            institucion VARCHAR(80) DEFAULT NULL,
            tipo       VARCHAR(20) NOT NULL,
            saldo_inicial DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            color      VARCHAR(7) NOT NULL DEFAULT \'#64748b\',
            activo     TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY (id),
            INDEX idx_wallet_cuenta_tenant (tenant_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE wallet_cuenta');
    }
}
