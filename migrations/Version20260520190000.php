<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tablas de módulos: work, wallet, fishing, legal, construccion, contact en la BD actual';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS contact (id INT AUTO_INCREMENT NOT NULL, tipo VARCHAR(20) NOT NULL, nombre VARCHAR(100) NOT NULL, apellido VARCHAR(100) DEFAULT NULL, empresa VARCHAR(150) DEFAULT NULL, email VARCHAR(150) DEFAULT NULL, telefono VARCHAR(30) DEFAULT NULL, direccion VARCHAR(255) DEFAULT NULL, ciudad VARCHAR(100) DEFAULT NULL, pais VARCHAR(50) DEFAULT NULL, notas LONGTEXT DEFAULT NULL, activo TINYINT NOT NULL, created_at DATETIME NOT NULL, INDEX idx_contact_tipo (tipo), INDEX idx_contact_nombre (apellido, nombre), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE IF NOT EXISTS wallet_category (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(80) NOT NULL, tipo VARCHAR(10) NOT NULL, icono VARCHAR(60) NOT NULL, color VARCHAR(7) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS wallet_entry (id INT AUTO_INCREMENT NOT NULL, monto NUMERIC(12, 2) NOT NULL, tipo VARCHAR(10) NOT NULL, descripcion VARCHAR(255) DEFAULT NULL, fecha DATE NOT NULL, created_at DATETIME NOT NULL, category_id INT DEFAULT NULL, INDEX IDX_5B26FB0A12469DE2 (category_id), INDEX idx_entry_fecha (fecha), INDEX idx_entry_tipo (tipo), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE IF NOT EXISTS work_client (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, cif_nif VARCHAR(30) DEFAULT NULL, direccion VARCHAR(255) DEFAULT NULL, emails_factura VARCHAR(255) NOT NULL, salario_base NUMERIC(8, 2) NOT NULL, bonus_dia NUMERIC(6, 2) NOT NULL, hora_limite_bonus VARCHAR(5) NOT NULL, banc_nombre VARCHAR(100) DEFAULT NULL, banc_direccion VARCHAR(100) DEFAULT NULL, banc_swift VARCHAR(20) DEFAULT NULL, banc_cuenta VARCHAR(50) DEFAULT NULL, banc_titular VARCHAR(100) DEFAULT NULL, recargo_hnl NUMERIC(8, 2) DEFAULT NULL, activo TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS work_day (id INT AUTO_INCREMENT NOT NULL, fecha DATE NOT NULL, hora_entrada VARCHAR(5) DEFAULT NULL, es_feriado TINYINT NOT NULL, es_vacacion TINYINT NOT NULL, notas VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, client_id INT NOT NULL, INDEX IDX_9FCE7E0C19EB6921 (client_id), INDEX idx_work_day_fecha (fecha), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS work_holiday (id INT AUTO_INCREMENT NOT NULL, fecha DATE NOT NULL, nombre VARCHAR(100) NOT NULL, anio INT NOT NULL, UNIQUE INDEX UNIQ_9510A161A8B7D9 (fecha), INDEX idx_work_holiday_anio (anio), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS work_vacation (id INT AUTO_INCREMENT NOT NULL, fecha_inicio DATE NOT NULL, fecha_fin DATE NOT NULL, dias INT NOT NULL, semestre INT NOT NULL, anio INT NOT NULL, notas VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS work_invoice (id INT AUTO_INCREMENT NOT NULL, anio INT NOT NULL, mes INT NOT NULL, dias_trabajados INT NOT NULL, dias_bonus INT NOT NULL, salario_base NUMERIC(8, 2) NOT NULL, monto_bonus NUMERIC(8, 2) NOT NULL, total NUMERIC(8, 2) NOT NULL, numero INT DEFAULT NULL, estado VARCHAR(20) NOT NULL, enviada_at DATETIME DEFAULT NULL, pagada_at DATETIME DEFAULT NULL, comision_banco_hnl NUMERIC(10, 2) DEFAULT NULL, recibo_swift INT DEFAULT NULL, tasa_emision_eur_l NUMERIC(14, 5) DEFAULT NULL, tasa_emision_usd_l NUMERIC(14, 5) DEFAULT NULL, tasa_emision_fecha VARCHAR(16) DEFAULT NULL, tasa_emision_source VARCHAR(64) DEFAULT NULL, tasa_pago_eur_l NUMERIC(14, 5) DEFAULT NULL, tasa_pago_usd_l NUMERIC(14, 5) DEFAULT NULL, tasa_pago_fecha VARCHAR(16) DEFAULT NULL, tasa_pago_source VARCHAR(64) DEFAULT NULL, payment_proof_stored_filename VARCHAR(180) DEFAULT NULL, payment_proof_original_name VARCHAR(255) DEFAULT NULL, payment_proof_mime VARCHAR(127) DEFAULT NULL, created_at DATETIME NOT NULL, client_id INT NOT NULL, INDEX IDX_45AEAF6619EB6921 (client_id), UNIQUE INDEX UNIQ_45AEAF6619EB692189A505776EC83E05 (client_id, anio, mes), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS work_invoice_bonus_line (id INT AUTO_INCREMENT NOT NULL, sort_order INT DEFAULT 0 NOT NULL, importe_eur NUMERIC(10, 2) NOT NULL, concepto VARCHAR(255) DEFAULT NULL, invoice_id INT NOT NULL, INDEX IDX_C7B49B7C2989F1FD (invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_finca (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, latitud NUMERIC(10, 7) DEFAULT NULL, longitud NUMERIC(10, 7) DEFAULT NULL, descripcion LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_lure (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, marca VARCHAR(100) DEFAULT NULL, color VARCHAR(50) DEFAULT NULL, tipo VARCHAR(50) DEFAULT NULL, precio NUMERIC(8, 2) DEFAULT NULL, tienda VARCHAR(150) DEFAULT NULL, propietario VARCHAR(100) DEFAULT NULL, propietario_user_id INT DEFAULT NULL, foto VARCHAR(255) DEFAULT NULL, notas LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_spot (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, latitud NUMERIC(10, 7) DEFAULT NULL, longitud NUMERIC(10, 7) DEFAULT NULL, profundidad_m DOUBLE PRECISION DEFAULT NULL, notas LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, finca_id INT NOT NULL, INDEX IDX_4E0FF4A29B7E9090 (finca_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_trip (id INT AUTO_INCREMENT NOT NULL, fecha DATE NOT NULL, notas LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, finca_id INT NOT NULL, INDEX IDX_816B7BEA9B7E9090 (finca_id), INDEX idx_fishing_trip_fecha (fecha), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_expense (id INT AUTO_INCREMENT NOT NULL, concepto VARCHAR(100) NOT NULL, monto NUMERIC(8, 2) NOT NULL, pagado_por VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, trip_id INT NOT NULL, INDEX IDX_5C192B82A5BC2E0E (trip_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_lure_result (id INT AUTO_INCREMENT NOT NULL, funciono TINYINT NOT NULL, notas LONGTEXT DEFAULT NULL, registrado_por_user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, lure_id INT NOT NULL, finca_id INT NOT NULL, INDEX IDX_7B4C68DD9C7FCD21 (lure_id), INDEX IDX_7B4C68DD9B7E9090 (finca_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_trip_lure (id INT AUTO_INCREMENT NOT NULL, funciono TINYINT NOT NULL, notas LONGTEXT DEFAULT NULL, trip_id INT NOT NULL, lure_id INT NOT NULL, INDEX IDX_C73AB68CA5BC2E0E (trip_id), INDEX IDX_C73AB68C9C7FCD21 (lure_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS fishing_trip_member (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nombre VARCHAR(100) NOT NULL, trip_id INT NOT NULL, INDEX IDX_F4E58EA2A5BC2E0E (trip_id), UNIQUE INDEX UNIQ_F4E58EA2A5BC2E0EA76ED395 (trip_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE IF NOT EXISTS construccion_proveedor (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, telefono VARCHAR(30) DEFAULT NULL, especialidad VARCHAR(20) NOT NULL, notas LONGTEXT DEFAULT NULL, activo TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS construccion_obra (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, descripcion LONGTEXT DEFAULT NULL, cliente_nombre VARCHAR(255) DEFAULT NULL, presupuesto NUMERIC(12, 2) DEFAULT NULL, estado VARCHAR(20) NOT NULL, fecha_inicio DATE DEFAULT NULL, fecha_fin DATE DEFAULT NULL, notas LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, cliente_id INT DEFAULT NULL, INDEX IDX_30422A55DE734E51 (cliente_id), INDEX idx_obra_estado (estado), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS construccion_gasto (id INT AUTO_INCREMENT NOT NULL, categoria VARCHAR(20) NOT NULL, descripcion VARCHAR(255) NOT NULL, monto NUMERIC(10, 2) NOT NULL, estado VARCHAR(20) NOT NULL, fecha DATE NOT NULL, notas LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, obra_id INT NOT NULL, proveedor_id INT DEFAULT NULL, INDEX IDX_8C1C193C2672C8 (obra_id), INDEX IDX_8C1C19CB305D73 (proveedor_id), INDEX idx_gasto_obra_fecha (obra_id, fecha), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE IF NOT EXISTS legal_case (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(50) DEFAULT NULL, tipo VARCHAR(20) NOT NULL, estado VARCHAR(20) NOT NULL, titulo VARCHAR(255) NOT NULL, descripcion LONGTEXT DEFAULT NULL, fecha_apertura DATE NOT NULL, fecha_cierre DATE DEFAULT NULL, honorarios NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME NOT NULL, contact_id INT NOT NULL, INDEX IDX_557377B3E7A1254A (contact_id), INDEX idx_legal_case_estado (estado), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS legal_follow_up (id INT AUTO_INCREMENT NOT NULL, descripcion LONGTEXT NOT NULL, fecha DATE NOT NULL, proxima_audiencia DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, case_id INT NOT NULL, INDEX IDX_977565CDCF10D4F5 (case_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS legal_payment (id INT AUTO_INCREMENT NOT NULL, concepto VARCHAR(150) NOT NULL, monto NUMERIC(10, 2) NOT NULL, estado VARCHAR(20) NOT NULL, fecha_pago DATE DEFAULT NULL, created_at DATETIME NOT NULL, case_id INT NOT NULL, INDEX IDX_AA131A63CF10D4F5 (case_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS legal_document (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(150) NOT NULL, archivo VARCHAR(255) NOT NULL, extension VARCHAR(10) DEFAULT NULL, created_at DATETIME NOT NULL, case_id INT NOT NULL, INDEX IDX_72A4FDB7CF10D4F5 (case_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        // Foreign keys
        $this->addSql('ALTER TABLE wallet_entry ADD CONSTRAINT FK_wallet_entry_category FOREIGN KEY (category_id) REFERENCES wallet_category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE work_day ADD CONSTRAINT FK_work_day_client FOREIGN KEY (client_id) REFERENCES work_client (id)');
        $this->addSql('ALTER TABLE work_invoice ADD CONSTRAINT FK_work_invoice_client FOREIGN KEY (client_id) REFERENCES work_client (id)');
        $this->addSql('ALTER TABLE work_invoice_bonus_line ADD CONSTRAINT FK_work_invoice_bonus_line_invoice FOREIGN KEY (invoice_id) REFERENCES work_invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fishing_spot ADD CONSTRAINT FK_fishing_spot_finca FOREIGN KEY (finca_id) REFERENCES fishing_finca (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fishing_trip ADD CONSTRAINT FK_fishing_trip_finca FOREIGN KEY (finca_id) REFERENCES fishing_finca (id)');
        $this->addSql('ALTER TABLE fishing_expense ADD CONSTRAINT FK_fishing_expense_trip FOREIGN KEY (trip_id) REFERENCES fishing_trip (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fishing_lure_result ADD CONSTRAINT FK_fishing_lure_result_lure FOREIGN KEY (lure_id) REFERENCES fishing_lure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fishing_lure_result ADD CONSTRAINT FK_fishing_lure_result_finca FOREIGN KEY (finca_id) REFERENCES fishing_finca (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fishing_trip_lure ADD CONSTRAINT FK_fishing_trip_lure_trip FOREIGN KEY (trip_id) REFERENCES fishing_trip (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fishing_trip_lure ADD CONSTRAINT FK_fishing_trip_lure_lure FOREIGN KEY (lure_id) REFERENCES fishing_lure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fishing_trip_member ADD CONSTRAINT FK_fishing_trip_member_trip FOREIGN KEY (trip_id) REFERENCES fishing_trip (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE construccion_obra ADD CONSTRAINT FK_construccion_obra_contact FOREIGN KEY (cliente_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE construccion_gasto ADD CONSTRAINT FK_construccion_gasto_obra FOREIGN KEY (obra_id) REFERENCES construccion_obra (id)');
        $this->addSql('ALTER TABLE construccion_gasto ADD CONSTRAINT FK_construccion_gasto_proveedor FOREIGN KEY (proveedor_id) REFERENCES construccion_proveedor (id)');
        $this->addSql('ALTER TABLE legal_case ADD CONSTRAINT FK_legal_case_contact FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE legal_follow_up ADD CONSTRAINT FK_legal_follow_up_case FOREIGN KEY (case_id) REFERENCES legal_case (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE legal_payment ADD CONSTRAINT FK_legal_payment_case FOREIGN KEY (case_id) REFERENCES legal_case (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE legal_document ADD CONSTRAINT FK_legal_document_case FOREIGN KEY (case_id) REFERENCES legal_case (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_entry DROP FOREIGN KEY FK_wallet_entry_category');
        $this->addSql('ALTER TABLE work_day DROP FOREIGN KEY FK_work_day_client');
        $this->addSql('ALTER TABLE work_invoice DROP FOREIGN KEY FK_work_invoice_client');
        $this->addSql('ALTER TABLE work_invoice_bonus_line DROP FOREIGN KEY FK_work_invoice_bonus_line_invoice');
        $this->addSql('ALTER TABLE fishing_spot DROP FOREIGN KEY FK_fishing_spot_finca');
        $this->addSql('ALTER TABLE fishing_trip DROP FOREIGN KEY FK_fishing_trip_finca');
        $this->addSql('ALTER TABLE fishing_expense DROP FOREIGN KEY FK_fishing_expense_trip');
        $this->addSql('ALTER TABLE fishing_lure_result DROP FOREIGN KEY FK_fishing_lure_result_lure');
        $this->addSql('ALTER TABLE fishing_lure_result DROP FOREIGN KEY FK_fishing_lure_result_finca');
        $this->addSql('ALTER TABLE fishing_trip_lure DROP FOREIGN KEY FK_fishing_trip_lure_trip');
        $this->addSql('ALTER TABLE fishing_trip_lure DROP FOREIGN KEY FK_fishing_trip_lure_lure');
        $this->addSql('ALTER TABLE fishing_trip_member DROP FOREIGN KEY FK_fishing_trip_member_trip');
        $this->addSql('ALTER TABLE construccion_obra DROP FOREIGN KEY FK_construccion_obra_contact');
        $this->addSql('ALTER TABLE construccion_gasto DROP FOREIGN KEY FK_construccion_gasto_obra');
        $this->addSql('ALTER TABLE construccion_gasto DROP FOREIGN KEY FK_construccion_gasto_proveedor');
        $this->addSql('ALTER TABLE legal_case DROP FOREIGN KEY FK_legal_case_contact');
        $this->addSql('ALTER TABLE legal_follow_up DROP FOREIGN KEY FK_legal_follow_up_case');
        $this->addSql('ALTER TABLE legal_payment DROP FOREIGN KEY FK_legal_payment_case');
        $this->addSql('ALTER TABLE legal_document DROP FOREIGN KEY FK_legal_document_case');
        $this->addSql('DROP TABLE IF EXISTS construccion_gasto');
        $this->addSql('DROP TABLE IF EXISTS construccion_obra');
        $this->addSql('DROP TABLE IF EXISTS construccion_proveedor');
        $this->addSql('DROP TABLE IF EXISTS contact');
        $this->addSql('DROP TABLE IF EXISTS fishing_expense');
        $this->addSql('DROP TABLE IF EXISTS fishing_finca');
        $this->addSql('DROP TABLE IF EXISTS fishing_lure');
        $this->addSql('DROP TABLE IF EXISTS fishing_lure_result');
        $this->addSql('DROP TABLE IF EXISTS fishing_spot');
        $this->addSql('DROP TABLE IF EXISTS fishing_trip');
        $this->addSql('DROP TABLE IF EXISTS fishing_trip_lure');
        $this->addSql('DROP TABLE IF EXISTS fishing_trip_member');
        $this->addSql('DROP TABLE IF EXISTS legal_case');
        $this->addSql('DROP TABLE IF EXISTS legal_document');
        $this->addSql('DROP TABLE IF EXISTS legal_follow_up');
        $this->addSql('DROP TABLE IF EXISTS legal_payment');
        $this->addSql('DROP TABLE IF EXISTS wallet_category');
        $this->addSql('DROP TABLE IF EXISTS wallet_entry');
        $this->addSql('DROP TABLE IF EXISTS work_client');
        $this->addSql('DROP TABLE IF EXISTS work_day');
        $this->addSql('DROP TABLE IF EXISTS work_holiday');
        $this->addSql('DROP TABLE IF EXISTS work_invoice');
        $this->addSql('DROP TABLE IF EXISTS work_invoice_bonus_line');
        $this->addSql('DROP TABLE IF EXISTS work_vacation');
    }
}
