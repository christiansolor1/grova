<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'wallet_entry: agregar es_transferencia para excluir transferencias de los totales de gastos/ingresos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_entry ADD COLUMN es_transferencia TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo');

        // Marcar entradas existentes de transferencias (descripción contiene "Transferencia →" o "Transferencia ←")
        $this->addSql("UPDATE wallet_entry SET es_transferencia = 1 WHERE descripcion LIKE 'Transferencia →%' OR descripcion LIKE 'Transferencia ←%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_entry DROP COLUMN es_transferencia');
    }
}
