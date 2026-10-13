<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Crea todas las tablas del módulo Planificación Financiera (Phase 1). */
final class Version20261004300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Módulo Planificación Financiera: fin_bolsa, fin_presupuesto, fin_asignacion, fin_ingreso, fin_compromiso, fin_cuota, fin_pago, fin_prestamo, fin_acreedor, fin_gasto, fin_evento, fin_meta, fin_inversion, fin_salud_mensual, fin_autoevaluacion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE fin_bolsa (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                tipo VARCHAR(20) NOT NULL DEFAULT 'necesidad',
                descripcion VARCHAR(300) DEFAULT NULL,
                porcentaje_sugerido DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                orden SMALLINT NOT NULL DEFAULT 0,
                color VARCHAR(7) DEFAULT NULL,
                activa TINYINT(1) NOT NULL DEFAULT 1,
                INDEX idx_fin_bolsa_tenant (tenant_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_presupuesto (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                anio SMALLINT NOT NULL,
                mes SMALLINT NOT NULL,
                tipo VARCHAR(10) NOT NULL DEFAULT 'libre',
                plantilla VARCHAR(20) NOT NULL DEFAULT 'babilonia_dabasir',
                estado VARCHAR(10) NOT NULL DEFAULT 'borrador',
                ingreso_neto_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                porcentajes_snapshot JSON DEFAULT NULL,
                nota VARCHAR(500) DEFAULT NULL,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uq_fin_presupuesto_tenant_mes (tenant_id, anio, mes),
                INDEX idx_fin_presupuesto_tenant (tenant_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_asignacion (
                id INT AUTO_INCREMENT NOT NULL,
                presupuesto_id INT NOT NULL,
                bolsa_id INT NOT NULL,
                porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                monto_asignado_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                INDEX idx_fin_asignacion_presupuesto (presupuesto_id),
                UNIQUE INDEX uq_fin_asignacion_presupuesto_bolsa (presupuesto_id, bolsa_id),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_asignacion_presupuesto FOREIGN KEY (presupuesto_id) REFERENCES fin_presupuesto (id) ON DELETE CASCADE,
                CONSTRAINT fk_fin_asignacion_bolsa FOREIGN KEY (bolsa_id) REFERENCES fin_bolsa (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_ingreso (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                presupuesto_id INT DEFAULT NULL,
                fuente VARCHAR(150) NOT NULL,
                fecha DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                monto_bruto DECIMAL(14,2) NOT NULL,
                comisiones DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                retenciones DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                moneda VARCHAR(3) NOT NULL DEFAULT 'HNL',
                tasa_cambio DECIMAL(14,5) NOT NULL DEFAULT 1.00000,
                neto_hnl DECIMAL(14,2) NOT NULL,
                ahorro_reservado_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                nota VARCHAR(300) DEFAULT NULL,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_fin_ingreso_tenant (tenant_id),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_ingreso_presupuesto FOREIGN KEY (presupuesto_id) REFERENCES fin_presupuesto (id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_compromiso (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                bolsa_id INT NOT NULL,
                nombre VARCHAR(150) NOT NULL,
                tipo VARCHAR(20) NOT NULL,
                monto_cuota DECIMAL(14,2) NOT NULL,
                moneda VARCHAR(3) NOT NULL DEFAULT 'HNL',
                frecuencia VARCHAR(15) NOT NULL DEFAULT 'mensual',
                dia_pago SMALLINT NOT NULL DEFAULT 1,
                fecha_inicio DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                fecha_fin DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                contacto VARCHAR(200) DEFAULT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_fin_compromiso_tenant (tenant_id),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_compromiso_bolsa FOREIGN KEY (bolsa_id) REFERENCES fin_bolsa (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_cuota (
                id INT AUTO_INCREMENT NOT NULL,
                compromiso_id INT NOT NULL,
                numero SMALLINT NOT NULL,
                fecha_vencimiento DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                capital DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                interes DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                otros DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                monto_total DECIMAL(14,2) NOT NULL,
                monto_pagado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                INDEX idx_fin_cuota_compromiso (compromiso_id),
                INDEX idx_fin_cuota_vencimiento (fecha_vencimiento),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_cuota_compromiso FOREIGN KEY (compromiso_id) REFERENCES fin_compromiso (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_pago (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                cuota_id INT DEFAULT NULL,
                compromiso_id INT NOT NULL,
                fecha DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                monto DECIMAL(14,2) NOT NULL,
                moneda VARCHAR(3) NOT NULL DEFAULT 'HNL',
                tasa_cambio DECIMAL(14,5) NOT NULL DEFAULT 1.00000,
                monto_hnl DECIMAL(14,2) NOT NULL,
                metodo VARCHAR(80) DEFAULT NULL,
                comprobante_path VARCHAR(500) DEFAULT NULL,
                nota VARCHAR(300) DEFAULT NULL,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_fin_pago_tenant (tenant_id),
                INDEX idx_fin_pago_cuota (cuota_id),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_pago_cuota FOREIGN KEY (cuota_id) REFERENCES fin_cuota (id) ON DELETE SET NULL,
                CONSTRAINT fk_fin_pago_compromiso FOREIGN KEY (compromiso_id) REFERENCES fin_compromiso (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_prestamo (
                id INT AUTO_INCREMENT NOT NULL,
                compromiso_id INT NOT NULL,
                capital DECIMAL(14,2) NOT NULL,
                tasa_anual DECIMAL(7,4) NOT NULL,
                plazo_meses SMALLINT NOT NULL,
                cuota_fija DECIMAL(14,2) NOT NULL,
                cargo_extra_cuota DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                fecha_inicio DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                UNIQUE INDEX uq_fin_prestamo_compromiso (compromiso_id),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_prestamo_compromiso FOREIGN KEY (compromiso_id) REFERENCES fin_compromiso (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_acreedor (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                compromiso_id INT DEFAULT NULL,
                nombre VARCHAR(150) NOT NULL,
                saldo_hnl DECIMAL(14,2) NOT NULL,
                tasa_anual DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
                tipo VARCHAR(20) NOT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                INDEX idx_fin_acreedor_tenant (tenant_id),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_acreedor_compromiso FOREIGN KEY (compromiso_id) REFERENCES fin_compromiso (id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_evento (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                nombre VARCHAR(150) NOT NULL,
                tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
                fecha_inicio DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                fecha_fin DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                presupuesto_estimado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                nota VARCHAR(300) DEFAULT NULL,
                cerrado TINYINT(1) NOT NULL DEFAULT 0,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_fin_evento_tenant (tenant_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_gasto (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                bolsa_id INT NOT NULL,
                presupuesto_id INT DEFAULT NULL,
                evento_id INT DEFAULT NULL,
                descripcion VARCHAR(200) NOT NULL,
                fecha DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                monto DECIMAL(14,2) NOT NULL,
                moneda VARCHAR(3) NOT NULL DEFAULT 'HNL',
                tasa_cambio DECIMAL(14,5) NOT NULL DEFAULT 1.00000,
                monto_hnl DECIMAL(14,2) NOT NULL,
                naturaleza VARCHAR(20) NOT NULL DEFAULT 'necesidad',
                metodo VARCHAR(80) DEFAULT NULL,
                comprobante_path VARCHAR(500) DEFAULT NULL,
                nota VARCHAR(300) DEFAULT NULL,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_fin_gasto_tenant (tenant_id),
                INDEX idx_fin_gasto_fecha (fecha),
                PRIMARY KEY(id),
                CONSTRAINT fk_fin_gasto_bolsa FOREIGN KEY (bolsa_id) REFERENCES fin_bolsa (id),
                CONSTRAINT fk_fin_gasto_presupuesto FOREIGN KEY (presupuesto_id) REFERENCES fin_presupuesto (id) ON DELETE SET NULL,
                CONSTRAINT fk_fin_gasto_evento FOREIGN KEY (evento_id) REFERENCES fin_evento (id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_meta (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                nombre VARCHAR(150) NOT NULL,
                tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
                monto_objetivo DECIMAL(14,2) NOT NULL,
                monto_acumulado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                aporte_mensual_sugerido DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                fecha_objetivo DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                activa TINYINT(1) NOT NULL DEFAULT 1,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_fin_meta_tenant (tenant_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_inversion (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                nombre VARCHAR(150) NOT NULL,
                tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
                institucion VARCHAR(150) DEFAULT NULL,
                monto_invertido_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                valor_actual_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                rendimiento_esperado DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
                rendimiento_real DECIMAL(7,4) DEFAULT NULL,
                fecha_inicio DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
                activa TINYINT(1) NOT NULL DEFAULT 1,
                nota VARCHAR(300) DEFAULT NULL,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_fin_inversion_tenant (tenant_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_salud_mensual (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                anio SMALLINT NOT NULL,
                mes SMALLINT NOT NULL,
                ingreso_neto_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                gasto_variable_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                gasto_fijo_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                ahorro_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                flujoneto_hnl DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                estado VARCHAR(10) NOT NULL DEFAULT 'neutro',
                puntuacion SMALLINT NOT NULL DEFAULT 0,
                porcentajes_reales JSON DEFAULT NULL,
                calculado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uq_fin_salud_tenant_mes (tenant_id, anio, mes),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE fin_autoevaluacion (
                id INT AUTO_INCREMENT NOT NULL,
                tenant_id INT NOT NULL,
                anio SMALLINT NOT NULL,
                mes SMALLINT NOT NULL,
                respuestas JSON NOT NULL,
                puntuacion SMALLINT NOT NULL DEFAULT 0,
                cumplio_babilonia TINYINT(1) NOT NULL DEFAULT 0,
                cumplio_regla TINYINT(1) NOT NULL DEFAULT 0,
                reflexion LONGTEXT DEFAULT NULL,
                creado_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX uq_fin_autoevaluacion_tenant_mes (tenant_id, anio, mes),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS fin_autoevaluacion');
        $this->addSql('DROP TABLE IF EXISTS fin_salud_mensual');
        $this->addSql('DROP TABLE IF EXISTS fin_inversion');
        $this->addSql('DROP TABLE IF EXISTS fin_meta');
        $this->addSql('DROP TABLE IF EXISTS fin_gasto');
        $this->addSql('DROP TABLE IF EXISTS fin_evento');
        $this->addSql('DROP TABLE IF EXISTS fin_acreedor');
        $this->addSql('DROP TABLE IF EXISTS fin_prestamo');
        $this->addSql('DROP TABLE IF EXISTS fin_pago');
        $this->addSql('DROP TABLE IF EXISTS fin_cuota');
        $this->addSql('DROP TABLE IF EXISTS fin_compromiso');
        $this->addSql('DROP TABLE IF EXISTS fin_ingreso');
        $this->addSql('DROP TABLE IF EXISTS fin_asignacion');
        $this->addSql('DROP TABLE IF EXISTS fin_presupuesto');
        $this->addSql('DROP TABLE IF EXISTS fin_bolsa');
    }
}
