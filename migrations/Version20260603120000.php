<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tablas wallet_gasto_fijo y wallet_gasto_fijo_pago';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE wallet_gasto_fijo (
            id INT AUTO_INCREMENT NOT NULL,
            tenant_id INT NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            dia_pago TINYINT NOT NULL DEFAULT 1,
            cuenta_id INT DEFAULT NULL,
            category_id INT DEFAULT NULL,
            nota VARCHAR(255) DEFAULT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            INDEX IDX_gasto_fijo_tenant (tenant_id),
            CONSTRAINT FK_gasto_fijo_cuenta FOREIGN KEY (cuenta_id) REFERENCES wallet_cuenta(id) ON DELETE SET NULL,
            CONSTRAINT FK_gasto_fijo_category FOREIGN KEY (category_id) REFERENCES wallet_category(id) ON DELETE SET NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('CREATE TABLE wallet_gasto_fijo_pago (
            id INT AUTO_INCREMENT NOT NULL,
            gasto_fijo_id INT NOT NULL,
            anio SMALLINT NOT NULL,
            mes TINYINT NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            wallet_entry_id INT DEFAULT NULL,
            pagado_at DATETIME NOT NULL,
            UNIQUE KEY uq_gasto_fijo_mes (gasto_fijo_id, anio, mes),
            CONSTRAINT FK_gasto_fijo_pago_gasto FOREIGN KEY (gasto_fijo_id) REFERENCES wallet_gasto_fijo(id) ON DELETE CASCADE,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE wallet_gasto_fijo_pago');
        $this->addSql('DROP TABLE wallet_gasto_fijo');
    }
}
