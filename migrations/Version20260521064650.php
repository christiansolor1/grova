<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521064650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Perfil de usuario (avatarUrl, telefono, etc.) + limpieza de FKs nombradas + notificacion tenant_slug';
    }

    public function up(Schema $schema): void
    {
        // FKs nombradas que pueden o no existir según el entorno — ignorar error si no existen
        $this->addSql('ALTER TABLE construccion_gasto DROP FOREIGN KEY IF EXISTS `FK_construccion_gasto_obra`');
        $this->addSql('ALTER TABLE construccion_gasto DROP FOREIGN KEY IF EXISTS `FK_construccion_gasto_proveedor`');
        $this->addSql('ALTER TABLE construccion_obra DROP FOREIGN KEY IF EXISTS `FK_construccion_obra_contact`');
        $this->addSql('ALTER TABLE fishing_expense DROP FOREIGN KEY IF EXISTS `FK_fishing_expense_trip`');
        $this->addSql('ALTER TABLE fishing_lure_result DROP FOREIGN KEY IF EXISTS `FK_fishing_lure_result_finca`');
        $this->addSql('ALTER TABLE fishing_lure_result DROP FOREIGN KEY IF EXISTS `FK_fishing_lure_result_lure`');
        $this->addSql('ALTER TABLE fishing_spot DROP FOREIGN KEY IF EXISTS `FK_fishing_spot_finca`');
        $this->addSql('ALTER TABLE fishing_trip DROP FOREIGN KEY IF EXISTS `FK_fishing_trip_finca`');
        $this->addSql('ALTER TABLE fishing_trip_lure DROP FOREIGN KEY IF EXISTS `FK_fishing_trip_lure_lure`');
        $this->addSql('ALTER TABLE fishing_trip_lure DROP FOREIGN KEY IF EXISTS `FK_fishing_trip_lure_trip`');
        $this->addSql('ALTER TABLE fishing_trip_member DROP FOREIGN KEY IF EXISTS `FK_fishing_trip_member_trip`');
        $this->addSql('ALTER TABLE legal_case DROP FOREIGN KEY IF EXISTS `FK_legal_case_contact`');
        $this->addSql('ALTER TABLE legal_document DROP FOREIGN KEY IF EXISTS `FK_legal_document_case`');
        $this->addSql('ALTER TABLE legal_follow_up DROP FOREIGN KEY IF EXISTS `FK_legal_follow_up_case`');
        $this->addSql('ALTER TABLE legal_payment DROP FOREIGN KEY IF EXISTS `FK_legal_payment_case`');
        $this->addSql('ALTER TABLE wallet_entry DROP FOREIGN KEY IF EXISTS `FK_wallet_entry_category`');
        $this->addSql('ALTER TABLE work_day DROP FOREIGN KEY IF EXISTS `FK_work_day_client`');
        $this->addSql('ALTER TABLE work_invoice DROP FOREIGN KEY IF EXISTS `FK_work_invoice_client`');
        $this->addSql('ALTER TABLE work_invoice_bonus_line DROP FOREIGN KEY IF EXISTS `FK_work_invoice_bonus_line_invoice`');

        // Ampliar tenant_slug en notification (puede ya estar a 60 — ignorar si igual)
        $this->addSql('ALTER TABLE notification CHANGE tenant_slug tenant_slug VARCHAR(60) DEFAULT NULL');

        // Eliminar índice único en tenant.db_name si existe
        $this->addSql('DROP INDEX IF EXISTS `UNIQ_4E59C462628DE0D9` ON tenant');

        // Campos de perfil en user (solo si no existen)
        $this->addSql("
            ALTER TABLE user
                ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(500) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS fecha_nacimiento DATETIME DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS genero VARCHAR(20) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS pais VARCHAR(100) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS ciudad VARCHAR(100) DEFAULT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification CHANGE tenant_slug tenant_slug VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E59C462628DE0D9 ON tenant (db_name)');
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS avatar_url, DROP COLUMN IF EXISTS telefono, DROP COLUMN IF EXISTS fecha_nacimiento, DROP COLUMN IF EXISTS genero, DROP COLUMN IF EXISTS pais, DROP COLUMN IF EXISTS ciudad');
    }
}
