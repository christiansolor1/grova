<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fishing module: fincas, spots, lures, trips, expenses + menu item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("USE grova_christian");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_finca (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                nombre      VARCHAR(100) NOT NULL,
                latitud     DECIMAL(10,7) DEFAULT NULL,
                longitud    DECIMAL(10,7) DEFAULT NULL,
                descripcion TEXT DEFAULT NULL,
                created_at  DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_spot (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                finca_id        INT NOT NULL,
                nombre          VARCHAR(100) NOT NULL,
                latitud         DECIMAL(10,7) DEFAULT NULL,
                longitud        DECIMAL(10,7) DEFAULT NULL,
                profundidad_m   DOUBLE DEFAULT NULL,
                notas           TEXT DEFAULT NULL,
                created_at      DATETIME NOT NULL,
                CONSTRAINT fk_spot_finca FOREIGN KEY (finca_id) REFERENCES fishing_finca(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_lure (
                id                   INT AUTO_INCREMENT PRIMARY KEY,
                nombre               VARCHAR(100) NOT NULL,
                marca                VARCHAR(100) DEFAULT NULL,
                color                VARCHAR(50)  DEFAULT NULL,
                tipo                 VARCHAR(50)  DEFAULT NULL,
                precio               DECIMAL(8,2) DEFAULT NULL,
                tienda               VARCHAR(150) DEFAULT NULL,
                propietario          VARCHAR(100) DEFAULT NULL,
                propietario_user_id  INT DEFAULT NULL,
                foto                 VARCHAR(255) DEFAULT NULL,
                notas                TEXT DEFAULT NULL,
                created_at           DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_lure_result (
                id                    INT AUTO_INCREMENT PRIMARY KEY,
                lure_id               INT NOT NULL,
                finca_id              INT NOT NULL,
                funciono              TINYINT(1) NOT NULL DEFAULT 1,
                notas                 TEXT DEFAULT NULL,
                registrado_por_user_id INT DEFAULT NULL,
                created_at            DATETIME NOT NULL,
                CONSTRAINT fk_lure_result_lure  FOREIGN KEY (lure_id)  REFERENCES fishing_lure(id)  ON DELETE CASCADE,
                CONSTRAINT fk_lure_result_finca FOREIGN KEY (finca_id) REFERENCES fishing_finca(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_trip (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                finca_id   INT NOT NULL,
                fecha      DATE NOT NULL,
                notas      TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_fishing_trip_fecha (fecha),
                CONSTRAINT fk_trip_finca FOREIGN KEY (finca_id) REFERENCES fishing_finca(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_trip_member (
                id      INT AUTO_INCREMENT PRIMARY KEY,
                trip_id INT NOT NULL,
                user_id INT NOT NULL,
                nombre  VARCHAR(100) NOT NULL,
                UNIQUE KEY uq_trip_member (trip_id, user_id),
                CONSTRAINT fk_tm_trip FOREIGN KEY (trip_id) REFERENCES fishing_trip(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_expense (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                trip_id    INT NOT NULL,
                concepto   VARCHAR(100) NOT NULL,
                monto      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                pagado_por VARCHAR(100) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_exp_trip FOREIGN KEY (trip_id) REFERENCES fishing_trip(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS fishing_trip_lure (
                id        INT AUTO_INCREMENT PRIMARY KEY,
                trip_id   INT NOT NULL,
                lure_id   INT NOT NULL,
                funciono  TINYINT(1) NOT NULL DEFAULT 1,
                notas     TEXT DEFAULT NULL,
                CONSTRAINT fk_tl_trip FOREIGN KEY (trip_id) REFERENCES fishing_trip(id) ON DELETE CASCADE,
                CONSTRAINT fk_tl_lure FOREIGN KEY (lure_id) REFERENCES fishing_lure(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // ── Menu en grova_core ───────────────────────────────────────────────
        $this->addSql("USE grova");
        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, label, icon, parent_key, sort_order, show_in_sidebar, dev_only, status)
            VALUES ('pesca', 'Pesca', 'bi bi-water', NULL, 30, 1, 0, 'activo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("USE grova_christian");
        $this->addSql("DROP TABLE IF EXISTS fishing_trip_lure");
        $this->addSql("DROP TABLE IF EXISTS fishing_expense");
        $this->addSql("DROP TABLE IF EXISTS fishing_trip_member");
        $this->addSql("DROP TABLE IF EXISTS fishing_trip");
        $this->addSql("DROP TABLE IF EXISTS fishing_lure_result");
        $this->addSql("DROP TABLE IF EXISTS fishing_lure");
        $this->addSql("DROP TABLE IF EXISTS fishing_spot");
        $this->addSql("DROP TABLE IF EXISTS fishing_finca");

        $this->addSql("USE grova");
        $this->addSql("DELETE FROM menu WHERE menu_key = 'pesca'");
    }
}
