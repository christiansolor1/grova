<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Módulo Wallet — Christian:
 * 1. Crea la BD grova_christian (si no existe)
 * 2. Crea tablas wallet_category y wallet_entry en grova_christian
 * 3. Inserta categorías semilla
 * 4. Agrega ítems de menú para Wallet
 */
final class Version20260508020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wallet module: create grova_christian DB, wallet tables, seed categories and menu items';
    }

    public function up(Schema $schema): void
    {
        // ── 1. Nota: la BD grova_christian debe existir antes de correr esta migración.
        // Crea la BD y permisos con root: ver instrucciones en el README.

        // ── 2. Tabla wallet_category ───────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS `grova_christian`.`wallet_category` (
                id     INT AUTO_INCREMENT NOT NULL,
                nombre VARCHAR(80)  NOT NULL,
                tipo   VARCHAR(10)  NOT NULL DEFAULT 'gasto',
                icono  VARCHAR(60)  NOT NULL DEFAULT 'bi-tag',
                color  VARCHAR(7)   NOT NULL DEFAULT '#64748b',
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // ── 3. Tabla wallet_entry ──────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS `grova_christian`.`wallet_entry` (
                id           INT AUTO_INCREMENT NOT NULL,
                category_id  INT          NULL,
                monto        DECIMAL(12,2) NOT NULL,
                tipo         VARCHAR(10)  NOT NULL,
                descripcion  VARCHAR(255) NULL,
                fecha        DATE         NOT NULL,
                created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_entry_fecha (fecha),
                KEY idx_entry_tipo  (tipo),
                CONSTRAINT fk_wallet_entry_category
                    FOREIGN KEY (category_id) REFERENCES `grova_christian`.`wallet_category`(id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // ── 4. Categorías semilla ──────────────────────────────────────────────
        // Ingresos
        $this->addSql("INSERT IGNORE INTO `grova_christian`.`wallet_category` (id, nombre, tipo, icono, color) VALUES
            (1,  'Salario / Nómina',   'ingreso', 'bi-briefcase-fill',   '#22c55e'),
            (2,  'Freelance',          'ingreso', 'bi-laptop',            '#10b981'),
            (3,  'Inversiones',        'ingreso', 'bi-graph-up-arrow',    '#06b6d4'),
            (4,  'Otros ingresos',     'ingreso', 'bi-plus-circle-fill',  '#84cc16'),
            (5,  'Alimentación',       'gasto',   'bi-basket2-fill',      '#f97316'),
            (6,  'Transporte',         'gasto',   'bi-car-front-fill',    '#f59e0b'),
            (7,  'Vivienda / Alquiler','gasto',   'bi-house-fill',        '#8b5cf6'),
            (8,  'Salud',              'gasto',   'bi-heart-pulse-fill',  '#ef4444'),
            (9,  'Entretenimiento',    'gasto',   'bi-controller',        '#ec4899'),
            (10, 'Suscripciones',      'gasto',   'bi-calendar-check',    '#6366f1'),
            (11, 'Tecnología',         'gasto',   'bi-cpu-fill',          '#0ea5e9'),
            (12, 'Educación',          'gasto',   'bi-book-fill',         '#14b8a6'),
            (13, 'Ropa y personal',    'gasto',   'bi-bag-fill',          '#a855f7'),
            (14, 'Ahorro',             'gasto',   'bi-piggy-bank-fill',   '#64748b'),
            (15, 'Otros gastos',       'gasto',   'bi-three-dots',        '#475569')
        ");

        // ── 5. Demo data — algunos movimientos de ejemplo ─────────────────────
        $this->addSql("INSERT IGNORE INTO `grova_christian`.`wallet_entry` (id, category_id, monto, tipo, descripcion, fecha) VALUES
            (1,  1,  3500.00, 'ingreso', 'Salario mayo',         '2026-05-01'),
            (2,  2,   850.00, 'ingreso', 'Proyecto grova',       '2026-05-03'),
            (3,  5,   120.50, 'gasto',   'Supermercado',         '2026-05-04'),
            (4,  6,    45.00, 'gasto',   'Gasolina',             '2026-05-05'),
            (5,  10,   15.99, 'gasto',   'Netflix',              '2026-05-01'),
            (6,  10,    9.99, 'gasto',   'Spotify',              '2026-05-01'),
            (7,  5,    55.00, 'gasto',   'Restaurante con amigos','2026-05-06'),
            (8,  11,  120.00, 'gasto',   'Dominio + hosting',    '2026-05-02'),
            (9,  3,   200.00, 'ingreso', 'Dividendos',           '2026-05-07'),
            (10, 8,    80.00, 'gasto',   'Farmacia',             '2026-05-04')
        ");

        // ── 6. Ítems de menú para Wallet ──────────────────────────────────────
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'wallet', NULL, 'Wallet', 'bi-wallet2', 10, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'wallet')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM menu WHERE menu_key = 'wallet'");
        $this->addSql('DROP TABLE IF EXISTS `grova_christian`.`wallet_entry`');
        $this->addSql('DROP TABLE IF EXISTS `grova_christian`.`wallet_category`');
        $this->addSql('DROP DATABASE IF EXISTS `grova_christian`');
    }
}
