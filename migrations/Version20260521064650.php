<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521064650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE construccion_gasto DROP FOREIGN KEY `FK_construccion_gasto_obra`');
        $this->addSql('ALTER TABLE construccion_gasto DROP FOREIGN KEY `FK_construccion_gasto_proveedor`');
        $this->addSql('ALTER TABLE construccion_obra DROP FOREIGN KEY `FK_construccion_obra_contact`');
        $this->addSql('ALTER TABLE fishing_expense DROP FOREIGN KEY `FK_fishing_expense_trip`');
        $this->addSql('ALTER TABLE fishing_lure_result DROP FOREIGN KEY `FK_fishing_lure_result_finca`');
        $this->addSql('ALTER TABLE fishing_lure_result DROP FOREIGN KEY `FK_fishing_lure_result_lure`');
        $this->addSql('ALTER TABLE fishing_spot DROP FOREIGN KEY `FK_fishing_spot_finca`');
        $this->addSql('ALTER TABLE fishing_trip DROP FOREIGN KEY `FK_fishing_trip_finca`');
        $this->addSql('ALTER TABLE fishing_trip_lure DROP FOREIGN KEY `FK_fishing_trip_lure_lure`');
        $this->addSql('ALTER TABLE fishing_trip_lure DROP FOREIGN KEY `FK_fishing_trip_lure_trip`');
        $this->addSql('ALTER TABLE fishing_trip_member DROP FOREIGN KEY `FK_fishing_trip_member_trip`');
        $this->addSql('ALTER TABLE legal_case DROP FOREIGN KEY `FK_legal_case_contact`');
        $this->addSql('ALTER TABLE legal_document DROP FOREIGN KEY `FK_legal_document_case`');
        $this->addSql('ALTER TABLE legal_follow_up DROP FOREIGN KEY `FK_legal_follow_up_case`');
        $this->addSql('ALTER TABLE legal_payment DROP FOREIGN KEY `FK_legal_payment_case`');
        $this->addSql('ALTER TABLE notification CHANGE tenant_slug tenant_slug VARCHAR(60) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_4E59C462628DE0D9 ON tenant');
        $this->addSql('ALTER TABLE user ADD avatar_url VARCHAR(500) DEFAULT NULL, ADD telefono VARCHAR(20) DEFAULT NULL, ADD fecha_nacimiento DATETIME DEFAULT NULL, ADD genero VARCHAR(20) DEFAULT NULL, ADD pais VARCHAR(100) DEFAULT NULL, ADD ciudad VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE wallet_entry DROP FOREIGN KEY `FK_wallet_entry_category`');
        $this->addSql('ALTER TABLE work_day DROP FOREIGN KEY `FK_work_day_client`');
        $this->addSql('ALTER TABLE work_invoice DROP FOREIGN KEY `FK_work_invoice_client`');
        $this->addSql('ALTER TABLE work_invoice_bonus_line DROP FOREIGN KEY `FK_work_invoice_bonus_line_invoice`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification CHANGE tenant_slug tenant_slug VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E59C462628DE0D9 ON tenant (db_name)');
        $this->addSql('ALTER TABLE user DROP avatar_url, DROP telefono, DROP fecha_nacimiento, DROP genero, DROP pais, DROP ciudad');
    }
}
