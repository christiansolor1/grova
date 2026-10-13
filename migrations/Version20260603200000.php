<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'wallet_gasto_fijo: tipo, dia_corte y campos de financiamiento';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_gasto_fijo
            ADD COLUMN tipo                     VARCHAR(20)    NOT NULL DEFAULT 'generico'   AFTER frecuencia,
            ADD COLUMN dia_corte                INT            NULL                           AFTER dia_pago,
            ADD COLUMN fecha_inicio_plan        DATE           NULL                           AFTER tipo,
            ADD COLUMN meses_financiamiento     INT            NULL                           AFTER fecha_inicio_plan,
            ADD COLUMN monto_prima              DECIMAL(10,2)  NULL                           AFTER meses_financiamiento,
            ADD COLUMN cuotas_especiales        TEXT           NULL                           AFTER monto_prima,
            ADD COLUMN monto_post_financiamiento DECIMAL(10,2) NULL                           AFTER cuotas_especiales
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_gasto_fijo
            DROP COLUMN tipo,
            DROP COLUMN dia_corte,
            DROP COLUMN fecha_inicio_plan,
            DROP COLUMN meses_financiamiento,
            DROP COLUMN monto_prima,
            DROP COLUMN cuotas_especiales,
            DROP COLUMN monto_post_financiamiento
        ");
    }
}
