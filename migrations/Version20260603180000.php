<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla wallet_transferencia';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE wallet_transferencia (
                id INT NOT NULL AUTO_INCREMENT,
                tenant_id INT NOT NULL,
                fecha DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                monto DECIMAL(10,2) NOT NULL DEFAULT '0.00',
                concepto VARCHAR(255) DEFAULT NULL,
                cuenta_origen_id INT DEFAULT NULL,
                cuenta_destino_id INT DEFAULT NULL,
                comision DECIMAL(10,2) DEFAULT NULL,
                entry_salida_id INT NOT NULL,
                entry_entrada_id INT NOT NULL,
                entry_comision_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                INDEX IDX_transferencia_tenant (tenant_id),
                INDEX IDX_transferencia_fecha (fecha),
                UNIQUE INDEX UNIQ_transferencia_salida (entry_salida_id),
                UNIQUE INDEX UNIQ_transferencia_entrada (entry_entrada_id),
                UNIQUE INDEX UNIQ_transferencia_comision (entry_comision_id),
                CONSTRAINT FK_transferencia_origen FOREIGN KEY (cuenta_origen_id) REFERENCES wallet_cuenta(id) ON DELETE SET NULL,
                CONSTRAINT FK_transferencia_destino FOREIGN KEY (cuenta_destino_id) REFERENCES wallet_cuenta(id) ON DELETE SET NULL,
                CONSTRAINT FK_transferencia_entry_salida FOREIGN KEY (entry_salida_id) REFERENCES wallet_entry(id) ON DELETE CASCADE,
                CONSTRAINT FK_transferencia_entry_entrada FOREIGN KEY (entry_entrada_id) REFERENCES wallet_entry(id) ON DELETE CASCADE,
                CONSTRAINT FK_transferencia_entry_comision FOREIGN KEY (entry_comision_id) REFERENCES wallet_entry(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE wallet_transferencia');
    }
}
