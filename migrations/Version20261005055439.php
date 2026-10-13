<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005055439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agenda_evento CHANGE avisos avisos JSON NOT NULL');
        $this->addSql('ALTER TABLE fin_acreedor CHANGE tasa_anual tasa_anual NUMERIC(7, 4) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE fin_acreedor RENAME INDEX fk_fin_acreedor_compromiso TO IDX_5B89BC41DDB11657');
        $this->addSql('ALTER TABLE fin_asignacion DROP FOREIGN KEY `fk_fin_asignacion_bolsa`');
        $this->addSql('ALTER TABLE fin_asignacion CHANGE porcentaje porcentaje NUMERIC(5, 2) NOT NULL, CHANGE monto_asignado_hnl monto_asignado_hnl NUMERIC(14, 2) NOT NULL');
        $this->addSql('ALTER TABLE fin_asignacion ADD CONSTRAINT FK_C00F011D51CC9DD9 FOREIGN KEY (bolsa_id) REFERENCES fin_bolsa (id)');
        $this->addSql('ALTER TABLE fin_asignacion RENAME INDEX idx_fin_asignacion_presupuesto TO IDX_C00F011D90119F0F');
        $this->addSql('ALTER TABLE fin_asignacion RENAME INDEX fk_fin_asignacion_bolsa TO IDX_C00F011D51CC9DD9');
        $this->addSql('ALTER TABLE fin_asignacion RENAME INDEX uq_fin_asignacion_presupuesto_bolsa TO uq_fin_asignacion');
        $this->addSql('ALTER TABLE fin_autoevaluacion CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_bolsa CHANGE tipo tipo VARCHAR(20) NOT NULL, CHANGE porcentaje_sugerido porcentaje_sugerido NUMERIC(5, 2) DEFAULT 0 NOT NULL, CHANGE orden orden INT NOT NULL');
        $this->addSql('ALTER TABLE fin_compromiso ADD descripcion VARCHAR(500) DEFAULT NULL, CHANGE dia_pago dia_pago SMALLINT NOT NULL, CHANGE fecha_inicio fecha_inicio DATE NOT NULL, CHANGE fecha_fin fecha_fin DATE DEFAULT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_compromiso RENAME INDEX fk_fin_compromiso_bolsa TO IDX_12B6AA8651CC9DD9');
        $this->addSql('ALTER TABLE fin_cuota CHANGE fecha_vencimiento fecha_vencimiento DATE NOT NULL, CHANGE capital capital NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE interes interes NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE otros otros NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE monto_pagado monto_pagado NUMERIC(14, 2) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE fin_evento CHANGE fecha_inicio fecha_inicio DATE DEFAULT NULL, CHANGE fecha_fin fecha_fin DATE DEFAULT NULL, CHANGE presupuesto_estimado presupuesto_estimado NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_gasto CHANGE fecha fecha DATE NOT NULL, CHANGE tasa_cambio tasa_cambio NUMERIC(14, 5) DEFAULT 1 NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_gasto RENAME INDEX fk_fin_gasto_bolsa TO IDX_48E4F78351CC9DD9');
        $this->addSql('ALTER TABLE fin_gasto RENAME INDEX fk_fin_gasto_presupuesto TO IDX_48E4F78390119F0F');
        $this->addSql('ALTER TABLE fin_gasto RENAME INDEX fk_fin_gasto_evento TO IDX_48E4F78387A5F842');
        $this->addSql('ALTER TABLE fin_ingreso CHANGE fuente fuente VARCHAR(200) NOT NULL, CHANGE fecha fecha DATE NOT NULL, CHANGE comisiones comisiones NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE retenciones retenciones NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE tasa_cambio tasa_cambio NUMERIC(14, 5) DEFAULT 1 NOT NULL, CHANGE ahorro_reservado_hnl ahorro_reservado_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_ingreso RENAME INDEX fk_fin_ingreso_presupuesto TO idx_fin_ingreso_presupuesto');
        $this->addSql('ALTER TABLE fin_inversion CHANGE monto_invertido_hnl monto_invertido_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE valor_actual_hnl valor_actual_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE rendimiento_esperado rendimiento_esperado NUMERIC(7, 4) DEFAULT 0 NOT NULL, CHANGE fecha_inicio fecha_inicio DATE DEFAULT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_meta CHANGE monto_acumulado monto_acumulado NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE aporte_mensual_sugerido aporte_mensual_sugerido NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE fecha_objetivo fecha_objetivo DATE DEFAULT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_pago CHANGE fecha fecha DATE NOT NULL, CHANGE tasa_cambio tasa_cambio NUMERIC(14, 5) DEFAULT 1 NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_pago RENAME INDEX fk_fin_pago_compromiso TO IDX_30AEC971DDB11657');
        $this->addSql('ALTER TABLE fin_prestamo CHANGE cargo_extra_cuota cargo_extra_cuota NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE fecha_inicio fecha_inicio DATE NOT NULL');
        $this->addSql('ALTER TABLE fin_prestamo RENAME INDEX uq_fin_prestamo_compromiso TO UNIQ_641CB6C5DDB11657');
        $this->addSql('ALTER TABLE fin_presupuesto CHANGE estado estado VARCHAR(20) DEFAULT \'borrador\' NOT NULL, CHANGE ingreso_neto_hnl ingreso_neto_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fin_salud_mensual CHANGE ingreso_neto_hnl ingreso_neto_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE gasto_variable_hnl gasto_variable_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE gasto_fijo_hnl gasto_fijo_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE ahorro_hnl ahorro_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE flujoneto_hnl flujoneto_hnl NUMERIC(14, 2) DEFAULT 0 NOT NULL, CHANGE calculado_at calculado_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE res_casa DROP FOREIGN KEY `FK_res_casa_manzana`');
        $this->addSql('ALTER TABLE res_casa DROP FOREIGN KEY `FK_res_casa_residencial`');
        $this->addSql('ALTER TABLE res_casa CHANGE tipo tipo VARCHAR(20) NOT NULL, CHANGE estado estado VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE res_casa ADD CONSTRAINT FK_3B0D8FB2FEC2D62A FOREIGN KEY (manzana_id) REFERENCES res_manzana (id)');
        $this->addSql('ALTER TABLE res_casa ADD CONSTRAINT FK_3B0D8FB263B54C3D FOREIGN KEY (residencial_id) REFERENCES res_residencial (id)');
        $this->addSql('ALTER TABLE res_casa RENAME INDEX idx_res_casa_manzana TO IDX_3B0D8FB2FEC2D62A');
        $this->addSql('ALTER TABLE res_casa RENAME INDEX idx_res_casa_residencial TO IDX_3B0D8FB263B54C3D');
        $this->addSql('ALTER TABLE res_incidente CHANGE tipo tipo VARCHAR(20) NOT NULL, CHANGE creado_en creado_en DATETIME NOT NULL');
        $this->addSql('ALTER TABLE res_incidente RENAME INDEX idx_res_incidente_guardia TO IDX_BE7EE94D10EE44F6');
        $this->addSql('ALTER TABLE res_manzana DROP FOREIGN KEY `FK_res_manzana_residencial`');
        $this->addSql('ALTER TABLE res_manzana ADD CONSTRAINT FK_3CB8456E63B54C3D FOREIGN KEY (residencial_id) REFERENCES res_residencial (id)');
        $this->addSql('ALTER TABLE res_manzana RENAME INDEX idx_res_manzana_residencial TO IDX_3CB8456E63B54C3D');
        $this->addSql('ALTER TABLE res_paquete CHANGE estado estado VARCHAR(20) NOT NULL, CHANGE recibido_en recibido_en DATETIME NOT NULL, CHANGE entregado_en entregado_en DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE res_paquete RENAME INDEX idx_res_paquete_residente TO IDX_302B3E7B59E2142F');
        $this->addSql('ALTER TABLE res_paquete RENAME INDEX idx_res_paquete_casa TO IDX_302B3E7B407243A2');
        $this->addSql('ALTER TABLE res_pase DROP FOREIGN KEY `FK_res_pase_visitante`');
        $this->addSql('ALTER TABLE res_pase CHANGE tipo tipo VARCHAR(20) NOT NULL, CHANGE fecha_inicio fecha_inicio DATE DEFAULT NULL, CHANGE fecha_fin fecha_fin DATE DEFAULT NULL, CHANGE dias_semana dias_semana JSON DEFAULT NULL, CHANGE estado estado VARCHAR(20) NOT NULL, CHANGE creado_en creado_en DATETIME NOT NULL');
        $this->addSql('ALTER TABLE res_pase ADD CONSTRAINT FK_7ECCB3DAD80AA8AF FOREIGN KEY (visitante_id) REFERENCES res_visitante (id)');
        $this->addSql('CREATE INDEX idx_res_pase_token ON res_pase (qr_token)');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX uniq_res_pase_token TO UNIQ_7ECCB3DA1AE26361');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX idx_res_pase_residente TO IDX_7ECCB3DA59E2142F');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX idx_res_pase_visitante TO IDX_7ECCB3DAD80AA8AF');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX idx_res_pase_casa_destino TO IDX_7ECCB3DACD71358E');
        $this->addSql('ALTER TABLE res_persona DROP FOREIGN KEY `FK_res_persona_casa`');
        $this->addSql('ALTER TABLE res_persona CHANGE tipo tipo VARCHAR(30) NOT NULL, CHANGE estado estado VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE res_persona ADD CONSTRAINT FK_73A6E275407243A2 FOREIGN KEY (casa_id) REFERENCES res_casa (id)');
        $this->addSql('ALTER TABLE res_persona RENAME INDEX idx_res_persona_casa TO IDX_73A6E275407243A2');
        $this->addSql('ALTER TABLE res_registro_entrada DROP FOREIGN KEY `FK_res_registro_pase`');
        $this->addSql('ALTER TABLE res_registro_entrada CHANGE tipo_entrada tipo_entrada VARCHAR(20) NOT NULL, CHANGE hora_entrada hora_entrada DATETIME NOT NULL, CHANGE hora_salida hora_salida DATETIME DEFAULT NULL, CHANGE es_primera_vez es_primera_vez TINYINT NOT NULL');
        $this->addSql('ALTER TABLE res_registro_entrada ADD CONSTRAINT FK_DC74A41992652A6 FOREIGN KEY (pase_id) REFERENCES res_pase (id)');
        $this->addSql('ALTER TABLE res_registro_entrada RENAME INDEX idx_res_registro_pase TO IDX_DC74A41992652A6');
        $this->addSql('ALTER TABLE res_registro_entrada RENAME INDEX idx_res_registro_visitante TO IDX_DC74A41D80AA8AF');
        $this->addSql('ALTER TABLE res_registro_entrada RENAME INDEX idx_res_registro_guardia TO IDX_DC74A4110EE44F6');
        $this->addSql('ALTER TABLE res_residencial CHANGE estado estado VARCHAR(20) NOT NULL, CHANGE mapa_json mapa_json JSON DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE res_visitante CHANGE creado_en creado_en DATETIME NOT NULL');
        $this->addSql('ALTER TABLE wallet_category RENAME INDEX fk_wcat_parent TO IDX_F2E9ADB5727ACA70');
        $this->addSql('DROP INDEX idx_wallet_cuenta_tenant ON wallet_cuenta');
        $this->addSql('ALTER TABLE wallet_cuenta CHANGE saldo_inicial saldo_inicial NUMERIC(12, 2) NOT NULL, CHANGE color color VARCHAR(7) NOT NULL, CHANGE activo activo TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE wallet_cuenta RENAME INDEX idx_cuenta_institucion TO IDX_8167208BB239FBC6');
        $this->addSql('ALTER TABLE wallet_entry CHANGE es_transferencia es_transferencia TINYINT NOT NULL');
        $this->addSql('ALTER TABLE wallet_entry RENAME INDEX idx_wallet_entry_cuenta TO IDX_5B26FB0A9AEFF118');
        $this->addSql('DROP INDEX IDX_gasto_fijo_tenant ON wallet_gasto_fijo');
        $this->addSql('ALTER TABLE wallet_gasto_fijo CHANGE monto monto NUMERIC(10, 2) NOT NULL, CHANGE dia_pago dia_pago INT NOT NULL, CHANGE clave_acceso clave_acceso LONGTEXT DEFAULT NULL, CHANGE activo activo TINYINT NOT NULL, CHANGE frecuencia frecuencia VARCHAR(20) NOT NULL, CHANGE tipo tipo VARCHAR(20) NOT NULL, CHANGE cuotas_especiales cuotas_especiales LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE wallet_gasto_fijo RENAME INDEX fk_gasto_fijo_cuenta TO IDX_7EBBFD109AEFF118');
        $this->addSql('ALTER TABLE wallet_gasto_fijo RENAME INDEX fk_gasto_fijo_category TO IDX_7EBBFD1012469DE2');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago CHANGE anio anio INT NOT NULL, CHANGE mes mes INT NOT NULL, CHANGE es_extra es_extra TINYINT NOT NULL, CHANGE fecha fecha DATE NOT NULL');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago RENAME INDEX idx_gasto_fijo_pago TO IDX_C5A13354B6E7B73A');
        $this->addSql('DROP INDEX IDX_inst_tenant ON wallet_institucion');
        $this->addSql('ALTER TABLE wallet_institucion CHANGE color color VARCHAR(7) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IDX_transferencia_tenant ON wallet_transferencia');
        $this->addSql('DROP INDEX IDX_transferencia_fecha ON wallet_transferencia');
        $this->addSql('ALTER TABLE wallet_transferencia CHANGE fecha fecha DATE NOT NULL, CHANGE monto monto NUMERIC(10, 2) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX fk_transferencia_origen TO IDX_DCE303C0BED08578');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX fk_transferencia_destino TO IDX_DCE303C05F10C30D');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX uniq_transferencia_salida TO UNIQ_DCE303C05ECAE013');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX uniq_transferencia_entrada TO UNIQ_DCE303C03E226B18');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX uniq_transferencia_comision TO UNIQ_DCE303C0837AD028');
        $this->addSql('ALTER TABLE work_client RENAME INDEX fk_work_client_wallet_cuenta TO IDX_6CBDC277CD73E189');
        $this->addSql('ALTER TABLE work_day CHANGE es_permiso es_permiso TINYINT NOT NULL, CHANGE es_incapacidad es_incapacidad TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agenda_evento CHANGE avisos avisos LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE fin_acreedor CHANGE tasa_anual tasa_anual NUMERIC(7, 4) DEFAULT \'0.0000\' NOT NULL');
        $this->addSql('ALTER TABLE fin_acreedor RENAME INDEX idx_5b89bc41ddb11657 TO fk_fin_acreedor_compromiso');
        $this->addSql('ALTER TABLE fin_asignacion DROP FOREIGN KEY FK_C00F011D51CC9DD9');
        $this->addSql('ALTER TABLE fin_asignacion CHANGE porcentaje porcentaje NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL, CHANGE monto_asignado_hnl monto_asignado_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE fin_asignacion ADD CONSTRAINT `fk_fin_asignacion_bolsa` FOREIGN KEY (bolsa_id) REFERENCES fin_bolsa (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fin_asignacion RENAME INDEX idx_c00f011d90119f0f TO idx_fin_asignacion_presupuesto');
        $this->addSql('ALTER TABLE fin_asignacion RENAME INDEX idx_c00f011d51cc9dd9 TO fk_fin_asignacion_bolsa');
        $this->addSql('ALTER TABLE fin_asignacion RENAME INDEX uq_fin_asignacion TO uq_fin_asignacion_presupuesto_bolsa');
        $this->addSql('ALTER TABLE fin_autoevaluacion CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_bolsa CHANGE tipo tipo VARCHAR(20) DEFAULT \'necesidad\' NOT NULL, CHANGE porcentaje_sugerido porcentaje_sugerido NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL, CHANGE orden orden SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE fin_compromiso DROP descripcion, CHANGE dia_pago dia_pago SMALLINT DEFAULT 1 NOT NULL, CHANGE fecha_inicio fecha_inicio DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE fecha_fin fecha_fin DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_compromiso RENAME INDEX idx_12b6aa8651cc9dd9 TO fk_fin_compromiso_bolsa');
        $this->addSql('ALTER TABLE fin_cuota CHANGE fecha_vencimiento fecha_vencimiento DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE capital capital NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE interes interes NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE otros otros NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE monto_pagado monto_pagado NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE fin_evento CHANGE fecha_inicio fecha_inicio DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE fecha_fin fecha_fin DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE presupuesto_estimado presupuesto_estimado NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_gasto CHANGE fecha fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE tasa_cambio tasa_cambio NUMERIC(14, 5) DEFAULT \'1.00000\' NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_gasto RENAME INDEX idx_48e4f78351cc9dd9 TO fk_fin_gasto_bolsa');
        $this->addSql('ALTER TABLE fin_gasto RENAME INDEX idx_48e4f78390119f0f TO fk_fin_gasto_presupuesto');
        $this->addSql('ALTER TABLE fin_gasto RENAME INDEX idx_48e4f78387a5f842 TO fk_fin_gasto_evento');
        $this->addSql('ALTER TABLE fin_ingreso CHANGE fecha fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE fuente fuente VARCHAR(150) NOT NULL, CHANGE comisiones comisiones NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE retenciones retenciones NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE tasa_cambio tasa_cambio NUMERIC(14, 5) DEFAULT \'1.00000\' NOT NULL, CHANGE ahorro_reservado_hnl ahorro_reservado_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_ingreso RENAME INDEX idx_fin_ingreso_presupuesto TO fk_fin_ingreso_presupuesto');
        $this->addSql('ALTER TABLE fin_inversion CHANGE monto_invertido_hnl monto_invertido_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE valor_actual_hnl valor_actual_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE rendimiento_esperado rendimiento_esperado NUMERIC(7, 4) DEFAULT \'0.0000\' NOT NULL, CHANGE fecha_inicio fecha_inicio DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_meta CHANGE monto_acumulado monto_acumulado NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE aporte_mensual_sugerido aporte_mensual_sugerido NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE fecha_objetivo fecha_objetivo DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_pago CHANGE fecha fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE tasa_cambio tasa_cambio NUMERIC(14, 5) DEFAULT \'1.00000\' NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_pago RENAME INDEX idx_30aec971ddb11657 TO fk_fin_pago_compromiso');
        $this->addSql('ALTER TABLE fin_prestamo CHANGE cargo_extra_cuota cargo_extra_cuota NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE fecha_inicio fecha_inicio DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE fin_prestamo RENAME INDEX uniq_641cb6c5ddb11657 TO uq_fin_prestamo_compromiso');
        $this->addSql('ALTER TABLE fin_presupuesto CHANGE estado estado VARCHAR(10) DEFAULT \'borrador\' NOT NULL, CHANGE ingreso_neto_hnl ingreso_neto_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE creado_at creado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE fin_salud_mensual CHANGE ingreso_neto_hnl ingreso_neto_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE gasto_variable_hnl gasto_variable_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE gasto_fijo_hnl gasto_fijo_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE ahorro_hnl ahorro_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE flujoneto_hnl flujoneto_hnl NUMERIC(14, 2) DEFAULT \'0.00\' NOT NULL, CHANGE calculado_at calculado_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE res_casa DROP FOREIGN KEY FK_3B0D8FB2FEC2D62A');
        $this->addSql('ALTER TABLE res_casa DROP FOREIGN KEY FK_3B0D8FB263B54C3D');
        $this->addSql('ALTER TABLE res_casa CHANGE tipo tipo VARCHAR(20) DEFAULT \'casa\' NOT NULL, CHANGE estado estado VARCHAR(20) DEFAULT \'vacia\' NOT NULL');
        $this->addSql('ALTER TABLE res_casa ADD CONSTRAINT `FK_res_casa_manzana` FOREIGN KEY (manzana_id) REFERENCES res_manzana (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_casa ADD CONSTRAINT `FK_res_casa_residencial` FOREIGN KEY (residencial_id) REFERENCES res_residencial (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE res_casa RENAME INDEX idx_3b0d8fb2fec2d62a TO IDX_res_casa_manzana');
        $this->addSql('ALTER TABLE res_casa RENAME INDEX idx_3b0d8fb263b54c3d TO IDX_res_casa_residencial');
        $this->addSql('ALTER TABLE res_incidente CHANGE tipo tipo VARCHAR(20) DEFAULT \'otro\' NOT NULL, CHANGE creado_en creado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE res_incidente RENAME INDEX idx_be7ee94d10ee44f6 TO IDX_res_incidente_guardia');
        $this->addSql('ALTER TABLE res_manzana DROP FOREIGN KEY FK_3CB8456E63B54C3D');
        $this->addSql('ALTER TABLE res_manzana ADD CONSTRAINT `FK_res_manzana_residencial` FOREIGN KEY (residencial_id) REFERENCES res_residencial (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE res_manzana RENAME INDEX idx_3cb8456e63b54c3d TO IDX_res_manzana_residencial');
        $this->addSql('ALTER TABLE res_paquete CHANGE estado estado VARCHAR(20) DEFAULT \'recibido\' NOT NULL, CHANGE recibido_en recibido_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE entregado_en entregado_en DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE res_paquete RENAME INDEX idx_302b3e7b59e2142f TO IDX_res_paquete_residente');
        $this->addSql('ALTER TABLE res_paquete RENAME INDEX idx_302b3e7b407243a2 TO IDX_res_paquete_casa');
        $this->addSql('ALTER TABLE res_pase DROP FOREIGN KEY FK_7ECCB3DAD80AA8AF');
        $this->addSql('DROP INDEX idx_res_pase_token ON res_pase');
        $this->addSql('ALTER TABLE res_pase CHANGE tipo tipo VARCHAR(20) DEFAULT \'unica_vez\' NOT NULL, CHANGE fecha_inicio fecha_inicio DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE fecha_fin fecha_fin DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE dias_semana dias_semana LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE estado estado VARCHAR(20) DEFAULT \'pendiente\' NOT NULL, CHANGE creado_en creado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE res_pase ADD CONSTRAINT `FK_res_pase_visitante` FOREIGN KEY (visitante_id) REFERENCES res_visitante (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX idx_7eccb3da59e2142f TO IDX_res_pase_residente');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX uniq_7eccb3da1ae26361 TO UNIQ_res_pase_token');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX idx_7eccb3dad80aa8af TO IDX_res_pase_visitante');
        $this->addSql('ALTER TABLE res_pase RENAME INDEX idx_7eccb3dacd71358e TO IDX_res_pase_casa_destino');
        $this->addSql('ALTER TABLE res_persona DROP FOREIGN KEY FK_73A6E275407243A2');
        $this->addSql('ALTER TABLE res_persona CHANGE tipo tipo VARCHAR(30) DEFAULT \'propietario\' NOT NULL, CHANGE estado estado VARCHAR(20) DEFAULT \'activo\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE res_persona ADD CONSTRAINT `FK_res_persona_casa` FOREIGN KEY (casa_id) REFERENCES res_casa (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_persona RENAME INDEX idx_73a6e275407243a2 TO IDX_res_persona_casa');
        $this->addSql('ALTER TABLE res_registro_entrada DROP FOREIGN KEY FK_DC74A41992652A6');
        $this->addSql('ALTER TABLE res_registro_entrada CHANGE tipo_entrada tipo_entrada VARCHAR(20) DEFAULT \'qr\' NOT NULL, CHANGE hora_entrada hora_entrada DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE hora_salida hora_salida DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE es_primera_vez es_primera_vez TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE res_registro_entrada ADD CONSTRAINT `FK_res_registro_pase` FOREIGN KEY (pase_id) REFERENCES res_pase (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_registro_entrada RENAME INDEX idx_dc74a41992652a6 TO IDX_res_registro_pase');
        $this->addSql('ALTER TABLE res_registro_entrada RENAME INDEX idx_dc74a41d80aa8af TO IDX_res_registro_visitante');
        $this->addSql('ALTER TABLE res_registro_entrada RENAME INDEX idx_dc74a4110ee44f6 TO IDX_res_registro_guardia');
        $this->addSql('ALTER TABLE res_residencial CHANGE estado estado VARCHAR(20) DEFAULT \'activa\' NOT NULL, CHANGE mapa_json mapa_json LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE res_visitante CHANGE creado_en creado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE wallet_category RENAME INDEX idx_f2e9adb5727aca70 TO fk_wcat_parent');
        $this->addSql('ALTER TABLE wallet_cuenta CHANGE saldo_inicial saldo_inicial NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, CHANGE color color VARCHAR(7) DEFAULT \'#64748b\' NOT NULL, CHANGE activo activo TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_wallet_cuenta_tenant ON wallet_cuenta (tenant_id)');
        $this->addSql('ALTER TABLE wallet_cuenta RENAME INDEX idx_8167208bb239fbc6 TO IDX_cuenta_institucion');
        $this->addSql('ALTER TABLE wallet_entry CHANGE es_transferencia es_transferencia TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE wallet_entry RENAME INDEX idx_5b26fb0a9aeff118 TO idx_wallet_entry_cuenta');
        $this->addSql('ALTER TABLE wallet_gasto_fijo CHANGE monto monto NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE dia_pago dia_pago TINYINT DEFAULT 1 NOT NULL, CHANGE frecuencia frecuencia VARCHAR(20) DEFAULT \'mensual\' NOT NULL, CHANGE tipo tipo VARCHAR(20) DEFAULT \'generico\' NOT NULL, CHANGE cuotas_especiales cuotas_especiales TEXT DEFAULT NULL, CHANGE clave_acceso clave_acceso TEXT DEFAULT NULL, CHANGE activo activo TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('CREATE INDEX IDX_gasto_fijo_tenant ON wallet_gasto_fijo (tenant_id)');
        $this->addSql('ALTER TABLE wallet_gasto_fijo RENAME INDEX idx_7ebbfd109aeff118 TO FK_gasto_fijo_cuenta');
        $this->addSql('ALTER TABLE wallet_gasto_fijo RENAME INDEX idx_7ebbfd1012469de2 TO FK_gasto_fijo_category');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago CHANGE anio anio SMALLINT NOT NULL, CHANGE mes mes TINYINT NOT NULL, CHANGE es_extra es_extra TINYINT DEFAULT 0 NOT NULL, CHANGE fecha fecha DATE DEFAULT \'2026-01-01\' NOT NULL');
        $this->addSql('ALTER TABLE wallet_gasto_fijo_pago RENAME INDEX idx_c5a13354b6e7b73a TO IDX_gasto_fijo_pago');
        $this->addSql('ALTER TABLE wallet_institucion CHANGE color color VARCHAR(7) DEFAULT \'#64748b\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_inst_tenant ON wallet_institucion (tenant_id)');
        $this->addSql('ALTER TABLE wallet_transferencia CHANGE fecha fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE monto monto NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_transferencia_tenant ON wallet_transferencia (tenant_id)');
        $this->addSql('CREATE INDEX IDX_transferencia_fecha ON wallet_transferencia (fecha)');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX idx_dce303c0bed08578 TO FK_transferencia_origen');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX uniq_dce303c0837ad028 TO UNIQ_transferencia_comision');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX idx_dce303c05f10c30d TO FK_transferencia_destino');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX uniq_dce303c05ecae013 TO UNIQ_transferencia_salida');
        $this->addSql('ALTER TABLE wallet_transferencia RENAME INDEX uniq_dce303c03e226b18 TO UNIQ_transferencia_entrada');
        $this->addSql('ALTER TABLE work_client RENAME INDEX idx_6cbdc277cd73e189 TO FK_work_client_wallet_cuenta');
        $this->addSql('ALTER TABLE work_day CHANGE es_permiso es_permiso TINYINT DEFAULT 0 NOT NULL, CHANGE es_incapacidad es_incapacidad TINYINT DEFAULT 0 NOT NULL');
    }
}
