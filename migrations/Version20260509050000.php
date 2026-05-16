<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Core contacts + Legal module: contact, legal_case, legal_follow_up, legal_payment, legal_document + menu items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("USE grova_christian");

        // ── Core: Contactos ──────────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS contact (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                tipo        VARCHAR(20)  NOT NULL DEFAULT 'cliente',
                nombre      VARCHAR(100) NOT NULL,
                apellido    VARCHAR(100) DEFAULT NULL,
                empresa     VARCHAR(150) DEFAULT NULL,
                email       VARCHAR(150) DEFAULT NULL,
                telefono    VARCHAR(30)  DEFAULT NULL,
                direccion   VARCHAR(255) DEFAULT NULL,
                ciudad      VARCHAR(100) DEFAULT NULL,
                pais        VARCHAR(50)  DEFAULT NULL,
                notas       TEXT DEFAULT NULL,
                activo      TINYINT(1) NOT NULL DEFAULT 1,
                created_at  DATETIME NOT NULL,
                INDEX idx_contact_tipo (tipo),
                INDEX idx_contact_nombre (apellido, nombre)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // ── Legal ─────────────────────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS legal_case (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                contact_id      INT NOT NULL,
                numero          VARCHAR(50)  DEFAULT NULL,
                tipo            VARCHAR(20)  NOT NULL DEFAULT 'civil',
                estado          VARCHAR(20)  NOT NULL DEFAULT 'abierto',
                titulo          VARCHAR(255) NOT NULL,
                descripcion     TEXT DEFAULT NULL,
                fecha_apertura  DATE NOT NULL,
                fecha_cierre    DATE DEFAULT NULL,
                honorarios      DECIMAL(10,2) DEFAULT NULL,
                created_at      DATETIME NOT NULL,
                INDEX idx_legal_case_estado (estado),
                CONSTRAINT fk_lcase_contact FOREIGN KEY (contact_id) REFERENCES contact(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS legal_follow_up (
                id                INT AUTO_INCREMENT PRIMARY KEY,
                case_id           INT NOT NULL,
                descripcion       TEXT NOT NULL,
                fecha             DATE NOT NULL,
                proxima_audiencia DATETIME DEFAULT NULL,
                created_at        DATETIME NOT NULL,
                CONSTRAINT fk_lfu_case FOREIGN KEY (case_id) REFERENCES legal_case(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS legal_payment (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                case_id     INT NOT NULL,
                concepto    VARCHAR(150) NOT NULL,
                monto       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                estado      VARCHAR(20) NOT NULL DEFAULT 'pendiente',
                fecha_pago  DATE DEFAULT NULL,
                created_at  DATETIME NOT NULL,
                CONSTRAINT fk_lpay_case FOREIGN KEY (case_id) REFERENCES legal_case(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS legal_document (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                case_id    INT NOT NULL,
                nombre     VARCHAR(150) NOT NULL,
                archivo    VARCHAR(255) NOT NULL,
                extension  VARCHAR(10)  DEFAULT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_ldoc_case FOREIGN KEY (case_id) REFERENCES legal_case(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // ── Menu ─────────────────────────────────────────────────────────────
        $this->addSql("USE grova");

        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, label, icon, parent_key, sort_order, show_in_sidebar, dev_only, status)
            VALUES
                ('contactos', 'Contactos', 'bi bi-people',     NULL, 40, 1, 0, 'activo'),
                ('legal',     'Legal',     'bi bi-briefcase2', NULL, 50, 1, 0, 'activo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("USE grova_christian");
        $this->addSql("DROP TABLE IF EXISTS legal_document");
        $this->addSql("DROP TABLE IF EXISTS legal_payment");
        $this->addSql("DROP TABLE IF EXISTS legal_follow_up");
        $this->addSql("DROP TABLE IF EXISTS legal_case");
        $this->addSql("DROP TABLE IF EXISTS contact");

        $this->addSql("USE grova");
        $this->addSql("DELETE FROM menu WHERE menu_key IN ('contactos','legal')");
    }
}
