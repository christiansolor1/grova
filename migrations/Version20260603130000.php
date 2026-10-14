<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega frecuencia, unidad, precio_unitario, cantidad_base a wallet_gasto_fijo; y cantidad, unidad, precio_unitario, es_extra, fecha a wallet_gasto_fijo_pago; elimina restricción única por mes';
    }

    public function up(Schema $schema): void
    {
        // Campos nuevos en gasto fijo
        $this->addSql("ALTER TABLE wallet_gasto_fijo
            ADD COLUMN IF NOT EXISTS frecuencia VARCHAR(20) NOT NULL DEFAULT 'mensual',
            ADD COLUMN IF NOT EXISTS unidad VARCHAR(50) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS precio_unitario DECIMAL(10,2) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS cantidad_base DECIMAL(8,2) DEFAULT NULL
        ");

        // Eliminar restricción única (permite múltiples pagos por mes)
        // MariaDB 10.4+: DROP FOREIGN KEY IF EXISTS / DROP INDEX IF EXISTS
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago DROP FOREIGN KEY IF EXISTS FK_gasto_fijo_pago_gasto');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago DROP INDEX IF EXISTS uq_gasto_fijo_mes');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago ADD INDEX IF NOT EXISTS idx_gasto_fijo_id (gasto_fijo_id)');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago ADD CONSTRAINT FK_gasto_fijo_pago_gasto FOREIGN KEY (gasto_fijo_id) REFERENCES wallet_gasto_fijo(id) ON DELETE CASCADE');

        // Campos nuevos en pago
        $this->addSql("ALTER TABLE wallet_gasto_fijo_pago
            ADD COLUMN IF NOT EXISTS cantidad DECIMAL(8,2) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS unidad VARCHAR(50) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS precio_unitario DECIMAL(10,2) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS es_extra TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN IF NOT EXISTS fecha DATE NOT NULL DEFAULT (CURRENT_DATE)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_gasto_fijo DROP COLUMN frecuencia, DROP COLUMN unidad, DROP COLUMN precio_unitario, DROP COLUMN cantidad_base');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago DROP COLUMN cantidad, DROP COLUMN unidad, DROP COLUMN precio_unitario, DROP COLUMN es_extra, DROP COLUMN fecha');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago ADD UNIQUE KEY uq_gasto_fijo_mes (gasto_fijo_id, anio, mes)');
    }
}
