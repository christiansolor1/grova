<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'wallet_gasto_fijo: agregar campos de acceso y pago (url_pago, numero_cliente, usuario_acceso, clave_acceso)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_gasto_fijo
            ADD COLUMN url_pago         VARCHAR(500) NULL AFTER nota,
            ADD COLUMN numero_cliente   VARCHAR(100) NULL AFTER url_pago,
            ADD COLUMN usuario_acceso   VARCHAR(150) NULL AFTER numero_cliente,
            ADD COLUMN clave_acceso     TEXT         NULL AFTER usuario_acceso
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_gasto_fijo
            DROP COLUMN url_pago,
            DROP COLUMN numero_cliente,
            DROP COLUMN usuario_acceso,
            DROP COLUMN clave_acceso
        ');
    }
}
