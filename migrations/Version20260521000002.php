<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega tenant_id a tablas del módulo Work (faltaba en tester)';
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'work_client',
            'work_day',
            'work_holiday',
            'work_invoice',
            'work_invoice_bonus_line',
            'work_vacation',
        ];

        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE `{$table}` ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 0 AFTER id");
        }
    }

    public function down(Schema $schema): void
    {
        // No revertir — tenant_id es necesario para el multi-tenant
    }
}
