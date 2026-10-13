<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla wallet_institucion y migra wallet_cuenta.institucion (string) a FK';
    }

    public function up(Schema $schema): void
    {
        // 1. Crear tabla de instituciones
        $this->addSql("
            CREATE TABLE wallet_institucion (
                id INT NOT NULL AUTO_INCREMENT,
                tenant_id INT NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                color VARCHAR(7) NOT NULL DEFAULT '#64748b',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                INDEX IDX_inst_tenant (tenant_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 2. Migrar valores existentes de wallet_cuenta.institucion → wallet_institucion
        $this->addSql("
            INSERT INTO wallet_institucion (tenant_id, nombre, color, created_at)
            SELECT DISTINCT tenant_id, institucion, '#64748b', NOW()
            FROM wallet_cuenta
            WHERE institucion IS NOT NULL AND institucion != ''
        ");

        // 3. Agregar columna FK en wallet_cuenta
        $this->addSql("ALTER TABLE wallet_cuenta ADD institucion_id INT DEFAULT NULL");

        // 4. Llenar FK con los IDs recién creados
        $this->addSql("
            UPDATE wallet_cuenta wc
            JOIN wallet_institucion wi ON wi.tenant_id = wc.tenant_id AND wi.nombre = wc.institucion
            SET wc.institucion_id = wi.id
            WHERE wc.institucion IS NOT NULL AND wc.institucion != ''
        ");

        // 5. Agregar FK constraint
        $this->addSql("
            ALTER TABLE wallet_cuenta
            ADD CONSTRAINT FK_cuenta_institucion FOREIGN KEY (institucion_id) REFERENCES wallet_institucion(id) ON DELETE SET NULL,
            ADD INDEX IDX_cuenta_institucion (institucion_id)
        ");

        // 6. Eliminar columna de texto vieja
        $this->addSql("ALTER TABLE wallet_cuenta DROP COLUMN institucion");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_cuenta DROP FOREIGN KEY FK_cuenta_institucion");
        $this->addSql("ALTER TABLE wallet_cuenta DROP INDEX IDX_cuenta_institucion");
        $this->addSql("ALTER TABLE wallet_cuenta ADD institucion VARCHAR(80) DEFAULT NULL");
        $this->addSql("
            UPDATE wallet_cuenta wc
            JOIN wallet_institucion wi ON wi.id = wc.institucion_id
            SET wc.institucion = wi.nombre
        ");
        $this->addSql("ALTER TABLE wallet_cuenta DROP COLUMN institucion_id");
        $this->addSql("DROP TABLE wallet_institucion");
    }
}
