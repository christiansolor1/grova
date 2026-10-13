<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603130001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campos a wallet_gasto_fijo_pago: cantidad, unidad, precio_unitario, es_extra, fecha; elimina restricción única por mes';
    }

    public function up(Schema $schema): void
    {
        // Soltar FK, luego el índice único, luego recrear FK con índice normal
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago DROP FOREIGN KEY FK_gasto_fijo_pago_gasto');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago DROP INDEX uq_gasto_fijo_mes');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago ADD INDEX IDX_gasto_fijo_pago (gasto_fijo_id)');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago ADD CONSTRAINT FK_gasto_fijo_pago_gasto FOREIGN KEY (gasto_fijo_id) REFERENCES wallet_gasto_fijo(id) ON DELETE CASCADE');
        $this->addSql("ALTER TABLE wallet_gasto_fijo_pago
            ADD COLUMN cantidad DECIMAL(8,2) DEFAULT NULL,
            ADD COLUMN unidad VARCHAR(50) DEFAULT NULL,
            ADD COLUMN precio_unitario DECIMAL(10,2) DEFAULT NULL,
            ADD COLUMN es_extra TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN fecha DATE NOT NULL DEFAULT '2026-01-01'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago DROP COLUMN cantidad, DROP COLUMN unidad, DROP COLUMN precio_unitario, DROP COLUMN es_extra, DROP COLUMN fecha');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago ADD UNIQUE KEY uq_gasto_fijo_mes (gasto_fijo_id, anio, mes)');
    }
}
