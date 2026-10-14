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
        // No-op: cambios ya aplicados por Version20260603130000
    }

    public function down(Schema $schema): void
    {
    }
}
