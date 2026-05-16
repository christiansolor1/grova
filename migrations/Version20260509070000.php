<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Construccion module: obras, gastos, proveedores + menu items for grova_christian and grova_jordi';
    }

    public function up(Schema $schema): void
    {
        // ── grova_christian ────────────────────────────────────────────────────
        $this->addSql("USE grova_christian");
        $this->addSql($this->createTablesSQL());
        // NOTE: grova_jordi will get these tables once that DB is created and
        // the tenant user has access. Run: USE grova_jordi; + createTablesSQL()

        // ── Menú en grova_core ────────────────────────────────────────────────
        $this->addSql("USE grova");

        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'construccion', NULL, 'Construcción', 'bi-building', 60, 1, 0, NULL, 'hecho'
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'construccion')
        ");
    }

    private function createTablesSQL(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS construccion_proveedor (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                nombre       VARCHAR(255) NOT NULL,
                telefono     VARCHAR(30)  DEFAULT NULL,
                especialidad VARCHAR(20)  NOT NULL DEFAULT 'materiales',
                notas        TEXT         DEFAULT NULL,
                activo       TINYINT(1)   NOT NULL DEFAULT 1,
                created_at   DATETIME     NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS construccion_obra (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id     INT          DEFAULT NULL,
                cliente_nombre VARCHAR(255) DEFAULT NULL,
                nombre         VARCHAR(255) NOT NULL,
                descripcion    TEXT         DEFAULT NULL,
                presupuesto    DECIMAL(12,2) DEFAULT NULL,
                estado         VARCHAR(20)  NOT NULL DEFAULT 'activa',
                fecha_inicio   DATE         DEFAULT NULL,
                fecha_fin      DATE         DEFAULT NULL,
                notas          TEXT         DEFAULT NULL,
                created_at     DATETIME     NOT NULL,
                INDEX idx_obra_estado (estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS construccion_gasto (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                obra_id      INT           NOT NULL,
                proveedor_id INT           DEFAULT NULL,
                categoria    VARCHAR(20)   NOT NULL DEFAULT 'material',
                descripcion  VARCHAR(255)  NOT NULL,
                monto        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                estado       VARCHAR(20)   NOT NULL DEFAULT 'pendiente',
                fecha        DATE          NOT NULL,
                notas        TEXT          DEFAULT NULL,
                created_at   DATETIME      NOT NULL,
                CONSTRAINT fk_gasto_obra     FOREIGN KEY (obra_id)      REFERENCES construccion_obra (id)      ON DELETE CASCADE,
                CONSTRAINT fk_gasto_prov     FOREIGN KEY (proveedor_id) REFERENCES construccion_proveedor (id) ON DELETE SET NULL,
                INDEX idx_gasto_obra_fecha (obra_id, fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
    }

    public function down(Schema $schema): void
    {
        $this->addSql("USE grova_christian");
        $this->addSql("DROP TABLE IF EXISTS construccion_gasto");
        $this->addSql("DROP TABLE IF EXISTS construccion_obra");
        $this->addSql("DROP TABLE IF EXISTS construccion_proveedor");
        $this->addSql("USE grova");
        $this->addSql("DELETE FROM menu WHERE menu_key = 'construccion'");
    }
}
