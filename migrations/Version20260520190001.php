<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520190001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna tenant_id a tablas de módulos que faltan';
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'construccion_gasto',
            'construccion_obra',
            'construccion_proveedor',
            'contact',
            'fishing_expense',
            'fishing_finca',
            'fishing_lure',
            'fishing_lure_result',
            'fishing_spot',
            'fishing_trip',
            'fishing_trip_lure',
            'fishing_trip_member',
            'legal_case',
            'legal_document',
            'legal_follow_up',
            'legal_payment',
        ];

        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE {$table} ADD tenant_id INT NOT NULL AFTER id");
        }
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'construccion_gasto',
            'construccion_obra',
            'construccion_proveedor',
            'contact',
            'fishing_expense',
            'fishing_finca',
            'fishing_lure',
            'fishing_lure_result',
            'fishing_spot',
            'fishing_trip',
            'fishing_trip_lure',
            'fishing_trip_member',
            'legal_case',
            'legal_document',
            'legal_follow_up',
            'legal_payment',
        ];

        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE {$table} DROP COLUMN tenant_id");
        }
    }
}
