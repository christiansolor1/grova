<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Núcleo multi-tenant: tablas plan, tenant, suscripcion, modulo_tenant.
 * Actualiza user con tenant_id, nombre, apellido.
 * Inserta datos semilla: planes, tenant grova_christian, suscripción y módulos.
 */
final class Version20260508010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Núcleo multi-tenant: plan, tenant, suscripcion, modulo_tenant + seed data';
    }

    public function up(Schema $schema): void
    {
        // ── Tabla plan ────────────────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS plan (
                id          INT AUTO_INCREMENT NOT NULL,
                nombre      VARCHAR(80)  NOT NULL,
                modulos     JSON         NOT NULL,
                precio_mensual DECIMAL(8,2) NOT NULL DEFAULT 0.00,
                estado      VARCHAR(20)  NOT NULL DEFAULT 'activo',
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // ── Tabla tenant ──────────────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS tenant (
                id         INT AUTO_INCREMENT NOT NULL,
                nombre     VARCHAR(100) NOT NULL,
                slug       VARCHAR(60)  NOT NULL,
                db_name    VARCHAR(80)  NOT NULL,
                estado     VARCHAR(20)  NOT NULL DEFAULT 'activo',
                created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_tenant_slug    (slug),
                UNIQUE KEY uq_tenant_db_name (db_name)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // ── Tabla suscripcion ─────────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS suscripcion (
                id                INT AUTO_INCREMENT NOT NULL,
                tenant_id         INT  NOT NULL,
                plan_id           INT  NOT NULL,
                fecha_inicio      DATE NOT NULL,
                fecha_vencimiento DATE NOT NULL,
                estado            VARCHAR(20) NOT NULL DEFAULT 'activa',
                PRIMARY KEY (id),
                CONSTRAINT fk_suscripcion_tenant FOREIGN KEY (tenant_id) REFERENCES tenant(id) ON DELETE CASCADE,
                CONSTRAINT fk_suscripcion_plan   FOREIGN KEY (plan_id)   REFERENCES plan(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // ── Tabla modulo_tenant ────────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE IF NOT EXISTS modulo_tenant (
                id         INT AUTO_INCREMENT NOT NULL,
                tenant_id  INT          NOT NULL,
                modulo_key VARCHAR(60)  NOT NULL,
                activo     TINYINT(1)   NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uq_modulo_tenant (tenant_id, modulo_key),
                CONSTRAINT fk_modulo_tenant FOREIGN KEY (tenant_id) REFERENCES tenant(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // ── Columnas en user ──────────────────────────────────────────────────
        $this->addSql("
            ALTER TABLE user
                ADD COLUMN IF NOT EXISTS tenant_id INT  NULL,
                ADD COLUMN IF NOT EXISTS nombre    VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS apellido  VARCHAR(100) NULL
        ");

        $this->addSql("
            ALTER TABLE user
                ADD CONSTRAINT fk_user_tenant FOREIGN KEY IF NOT EXISTS (tenant_id) REFERENCES tenant(id) ON DELETE SET NULL
        ");

        // ═════════════════════════════════════════════════════════════════════
        // SEED DATA
        // ═════════════════════════════════════════════════════════════════════

        // ── Planes ────────────────────────────────────────────────────────────
        $this->addSql("
            INSERT INTO plan (nombre, modulos, precio_mensual, estado)
            SELECT 'Free', JSON_ARRAY('wallet','work','agenda','habitos','pesca'), 0.00, 'activo'
            WHERE NOT EXISTS (SELECT 1 FROM plan WHERE nombre = 'Free')
        ");

        $this->addSql("
            INSERT INTO plan (nombre, modulos, precio_mensual, estado)
            SELECT 'Pro', JSON_ARRAY('wallet','work','agenda','habitos','pesca','facturacion','legal','construccion','pos','restaurante','clinica','financiera','inventario','rrhh'), 49.00, 'activo'
            WHERE NOT EXISTS (SELECT 1 FROM plan WHERE nombre = 'Pro')
        ");

        // ── Tenants de cortesía ────────────────────────────────────────────────
        $this->addSql("
            INSERT INTO tenant (nombre, slug, db_name, estado)
            SELECT 'Christian', 'grova_christian', 'grova_christian', 'activo'
            WHERE NOT EXISTS (SELECT 1 FROM tenant WHERE slug = 'grova_christian')
        ");

        $this->addSql("
            INSERT INTO tenant (nombre, slug, db_name, estado)
            SELECT 'Bufete Alcides', 'grova_alcides', 'grova_alcides', 'activo'
            WHERE NOT EXISTS (SELECT 1 FROM tenant WHERE slug = 'grova_alcides')
        ");

        $this->addSql("
            INSERT INTO tenant (nombre, slug, db_name, estado)
            SELECT 'Jordi Construcción', 'grova_jordi', 'grova_jordi', 'activo'
            WHERE NOT EXISTS (SELECT 1 FROM tenant WHERE slug = 'grova_jordi')
        ");

        $this->addSql("
            INSERT INTO tenant (nombre, slug, db_name, estado)
            SELECT 'Pesca Grupo', 'grova_pesca', 'grova_pesca', 'activo'
            WHERE NOT EXISTS (SELECT 1 FROM tenant WHERE slug = 'grova_pesca')
        ");

        // ── Suscripciones ─────────────────────────────────────────────────────
        // Christian → Free (hasta 2027-01-01)
        $this->addSql("
            INSERT INTO suscripcion (tenant_id, plan_id, fecha_inicio, fecha_vencimiento, estado)
            SELECT t.id, p.id, '2026-01-01', '2027-01-01', 'activa'
            FROM tenant t, plan p
            WHERE t.slug = 'grova_christian' AND p.nombre = 'Free'
              AND NOT EXISTS (SELECT 1 FROM suscripcion s WHERE s.tenant_id = t.id AND s.estado = 'activa')
        ");

        // Alcides → Pro
        $this->addSql("
            INSERT INTO suscripcion (tenant_id, plan_id, fecha_inicio, fecha_vencimiento, estado)
            SELECT t.id, p.id, '2026-01-01', '2027-01-01', 'activa'
            FROM tenant t, plan p
            WHERE t.slug = 'grova_alcides' AND p.nombre = 'Pro'
              AND NOT EXISTS (SELECT 1 FROM suscripcion s WHERE s.tenant_id = t.id AND s.estado = 'activa')
        ");

        // Jordi → Pro
        $this->addSql("
            INSERT INTO suscripcion (tenant_id, plan_id, fecha_inicio, fecha_vencimiento, estado)
            SELECT t.id, p.id, '2026-01-01', '2027-01-01', 'activa'
            FROM tenant t, plan p
            WHERE t.slug = 'grova_jordi' AND p.nombre = 'Pro'
              AND NOT EXISTS (SELECT 1 FROM suscripcion s WHERE s.tenant_id = t.id AND s.estado = 'activa')
        ");

        // Pesca → Free
        $this->addSql("
            INSERT INTO suscripcion (tenant_id, plan_id, fecha_inicio, fecha_vencimiento, estado)
            SELECT t.id, p.id, '2026-01-01', '2027-01-01', 'activa'
            FROM tenant t, plan p
            WHERE t.slug = 'grova_pesca' AND p.nombre = 'Free'
              AND NOT EXISTS (SELECT 1 FROM suscripcion s WHERE s.tenant_id = t.id AND s.estado = 'activa')
        ");

        // ── Módulos activos — Christian ────────────────────────────────────────
        foreach (['wallet', 'work', 'agenda', 'habitos', 'pesca'] as $key) {
            $this->addSql("
                INSERT INTO modulo_tenant (tenant_id, modulo_key, activo)
                SELECT id, '$key', 1 FROM tenant WHERE slug = 'grova_christian'
                  AND NOT EXISTS (SELECT 1 FROM modulo_tenant m WHERE m.tenant_id = tenant.id AND m.modulo_key = '$key')
            ");
        }

        // ── Módulos activos — Alcides ──────────────────────────────────────────
        foreach (['facturacion', 'legal'] as $key) {
            $this->addSql("
                INSERT INTO modulo_tenant (tenant_id, modulo_key, activo)
                SELECT id, '$key', 1 FROM tenant WHERE slug = 'grova_alcides'
                  AND NOT EXISTS (SELECT 1 FROM modulo_tenant m WHERE m.tenant_id = tenant.id AND m.modulo_key = '$key')
            ");
        }

        // ── Módulos activos — Jordi ────────────────────────────────────────────
        foreach (['construccion', 'facturacion', 'rrhh'] as $key) {
            $this->addSql("
                INSERT INTO modulo_tenant (tenant_id, modulo_key, activo)
                SELECT id, '$key', 1 FROM tenant WHERE slug = 'grova_jordi'
                  AND NOT EXISTS (SELECT 1 FROM modulo_tenant m WHERE m.tenant_id = tenant.id AND m.modulo_key = '$key')
            ");
        }

        // ── Módulos activos — Pesca ────────────────────────────────────────────
        $this->addSql("
            INSERT INTO modulo_tenant (tenant_id, modulo_key, activo)
            SELECT id, 'pesca', 1 FROM tenant WHERE slug = 'grova_pesca'
              AND NOT EXISTS (SELECT 1 FROM modulo_tenant m WHERE m.tenant_id = tenant.id AND m.modulo_key = 'pesca')
        ");

        // ── Vincular usuario a tenant ─────────────────────────────────────────
        // Asume que el usuario admin tiene el email con 'christian' o es el primer admin.
        // Solo actualiza si aún no tiene tenant asignado.
        $this->addSql("
            UPDATE user u
            JOIN tenant t ON t.slug = 'grova_christian'
            SET u.tenant_id = t.id, u.nombre = 'Christian', u.apellido = 'Solórzano'
            WHERE u.email LIKE '%christian%'
              AND u.tenant_id IS NULL
            LIMIT 1
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY IF EXISTS fk_user_tenant');
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS tenant_id, DROP COLUMN IF EXISTS nombre, DROP COLUMN IF EXISTS apellido');
        $this->addSql('DROP TABLE IF EXISTS modulo_tenant');
        $this->addSql('DROP TABLE IF EXISTS suscripcion');
        $this->addSql('DROP TABLE IF EXISTS tenant');
        $this->addSql('DROP TABLE IF EXISTS plan');
    }
}
