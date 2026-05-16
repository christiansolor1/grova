<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work module: work_client, work_day, work_holiday, work_vacation, work_invoice + seed Tasarauto + Honduras holidays 2026 + work menu item';
    }

    public function up(Schema $schema): void
    {
        // ── Tablas en grova_christian ────────────────────────────────────────
        $this->addSql("USE grova_christian");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS work_client (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                nombre          VARCHAR(100) NOT NULL,
                emails_factura  VARCHAR(255) NOT NULL,
                salario_base    DECIMAL(8,2) NOT NULL DEFAULT 1100.00,
                bonus_dia       DECIMAL(6,2) NOT NULL DEFAULT 12.50,
                hora_limite_bonus VARCHAR(5) NOT NULL DEFAULT '08:00',
                banc_nombre     VARCHAR(100) DEFAULT NULL,
                banc_direccion  VARCHAR(100) DEFAULT NULL,
                banc_swift      VARCHAR(20)  DEFAULT NULL,
                banc_cuenta     VARCHAR(50)  DEFAULT NULL,
                banc_titular    VARCHAR(100) DEFAULT NULL,
                activo          TINYINT(1) NOT NULL DEFAULT 1,
                created_at      DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS work_day (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                client_id    INT NOT NULL,
                fecha        DATE NOT NULL,
                hora_entrada VARCHAR(5) DEFAULT NULL,
                es_feriado   TINYINT(1) NOT NULL DEFAULT 0,
                es_vacacion  TINYINT(1) NOT NULL DEFAULT 0,
                notas        VARCHAR(255) DEFAULT NULL,
                created_at   DATETIME NOT NULL,
                UNIQUE KEY uq_work_day_fecha (fecha),
                INDEX idx_work_day_fecha (fecha),
                CONSTRAINT fk_work_day_client FOREIGN KEY (client_id) REFERENCES work_client(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS work_holiday (
                id     INT AUTO_INCREMENT PRIMARY KEY,
                fecha  DATE NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                anio   INT NOT NULL,
                UNIQUE KEY uq_work_holiday_fecha (fecha),
                INDEX idx_work_holiday_anio (anio)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS work_vacation (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                fecha_inicio DATE NOT NULL,
                fecha_fin    DATE NOT NULL,
                dias         INT NOT NULL DEFAULT 0,
                semestre     INT NOT NULL DEFAULT 1,
                anio         INT NOT NULL,
                notas        VARCHAR(255) DEFAULT NULL,
                created_at   DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS work_invoice (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                client_id        INT NOT NULL,
                anio             INT NOT NULL,
                mes              INT NOT NULL,
                dias_trabajados  INT NOT NULL DEFAULT 0,
                dias_bonus       INT NOT NULL DEFAULT 0,
                salario_base     DECIMAL(8,2) NOT NULL DEFAULT 1100.00,
                monto_bonus      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                total            DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                estado           VARCHAR(20) NOT NULL DEFAULT 'borrador',
                enviada_at       DATETIME DEFAULT NULL,
                created_at       DATETIME NOT NULL,
                UNIQUE KEY uq_work_invoice_client_mes (client_id, anio, mes),
                CONSTRAINT fk_work_invoice_client FOREIGN KEY (client_id) REFERENCES work_client(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // ── Seed: Tasarauto ──────────────────────────────────────────────────
        $this->addSql("
            INSERT INTO work_client (nombre, emails_factura, salario_base, bonus_dia, hora_limite_bonus,
                banc_nombre, banc_direccion, banc_swift, banc_cuenta, banc_titular, activo, created_at)
            SELECT 'Tasarauto',
                'sonia.puente@tasarauto.es,manuelcuesta@tasarauto.es',
                1100.00, 12.50, '08:00',
                'Banco Atlántida',
                'Bulevar Centroamérica, Plaza Banca Atlántida frente al INPREMA, Tegucigalpa, Honduras',
                'ATTDHNTE', '070120152577', 'Christian David Solorzano Girón', 1, NOW()
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM work_client WHERE nombre = 'Tasarauto')
        ");

        // ── Seed: Feriados Honduras 2026 ─────────────────────────────────────
        $this->addSql("
            INSERT IGNORE INTO work_holiday (fecha, nombre, anio) VALUES
            ('2026-01-01', 'Año Nuevo',                  2026),
            ('2026-01-06', 'Día de los Reyes Magos',     2026),
            ('2026-02-03', 'Nuestra Señora de Suyapa',   2026),
            ('2026-04-02', 'Jueves Santo',                2026),
            ('2026-04-03', 'Viernes Santo',               2026),
            ('2026-04-14', 'Día de las Américas',        2026),
            ('2026-05-01', 'Día del Trabajo',            2026),
            ('2026-09-15', 'Día de la Independencia',    2026),
            ('2026-10-01', 'Día de Choluteca',           2026),
            ('2026-10-07', 'Día del Soldado',            2026),
            ('2026-10-08', 'Día de la Raza',             2026),
            ('2026-10-09', 'Día de las Fuerzas Armadas', 2026),
            ('2026-11-01', 'Día de Todos los Santos',    2026),
            ('2026-12-25', 'Navidad',                    2026)
        ");

        // ── Menu: Work en grova_core ─────────────────────────────────────────
        $this->addSql("USE grova");

        $this->addSql("
            INSERT IGNORE INTO menu (menu_key, label, icon, parent_key, sort_order, show_in_sidebar, dev_only, status)
            VALUES ('work', 'Work', 'bi bi-briefcase', NULL, 20, 1, 0, 'activo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("USE grova_christian");
        $this->addSql("DROP TABLE IF EXISTS work_invoice");
        $this->addSql("DROP TABLE IF EXISTS work_vacation");
        $this->addSql("DROP TABLE IF EXISTS work_holiday");
        $this->addSql("DROP TABLE IF EXISTS work_day");
        $this->addSql("DROP TABLE IF EXISTS work_client");

        $this->addSql("USE grova");
        $this->addSql("DELETE FROM menu WHERE menu_key = 'work'");
    }
}
