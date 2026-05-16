<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508203624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_sidebar_parent_key ON menu');
        $this->addSql('ALTER TABLE menu CHANGE show_in_sidebar show_in_sidebar TINYINT DEFAULT 1 NOT NULL, CHANGE dev_only dev_only TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE menu RENAME INDEX uniq_sidebar_menu_key TO uniq_menu_key');
        $this->addSql('ALTER TABLE modulo_tenant CHANGE activo activo TINYINT NOT NULL');
        $this->addSql('ALTER TABLE plan CHANGE precio_mensual precio_mensual NUMERIC(8, 2) NOT NULL, CHANGE estado estado VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE suscripcion CHANGE estado estado VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE suscripcion RENAME INDEX fk_suscripcion_tenant TO IDX_497FA09033212A');
        $this->addSql('ALTER TABLE suscripcion RENAME INDEX fk_suscripcion_plan TO IDX_497FA0E899029B');
        $this->addSql('ALTER TABLE tenant CHANGE estado estado VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tenant RENAME INDEX uq_tenant_slug TO UNIQ_4E59C462989D9B62');
        $this->addSql('ALTER TABLE tenant RENAME INDEX uq_tenant_db_name TO UNIQ_4E59C462628DE0D9');
        $this->addSql('ALTER TABLE user RENAME INDEX fk_user_tenant TO IDX_8D93D6499033212A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu CHANGE show_in_sidebar show_in_sidebar TINYINT NOT NULL, CHANGE dev_only dev_only TINYINT NOT NULL');
        $this->addSql('CREATE INDEX idx_sidebar_parent_key ON menu (parent_key)');
        $this->addSql('ALTER TABLE menu RENAME INDEX uniq_menu_key TO uniq_sidebar_menu_key');
        $this->addSql('ALTER TABLE modulo_tenant CHANGE activo activo TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE plan CHANGE precio_mensual precio_mensual NUMERIC(8, 2) DEFAULT \'0.00\' NOT NULL, CHANGE estado estado VARCHAR(20) DEFAULT \'activo\' NOT NULL');
        $this->addSql('ALTER TABLE suscripcion CHANGE estado estado VARCHAR(20) DEFAULT \'activa\' NOT NULL');
        $this->addSql('ALTER TABLE suscripcion RENAME INDEX idx_497fa09033212a TO fk_suscripcion_tenant');
        $this->addSql('ALTER TABLE suscripcion RENAME INDEX idx_497fa0e899029b TO fk_suscripcion_plan');
        $this->addSql('ALTER TABLE tenant CHANGE estado estado VARCHAR(20) DEFAULT \'activo\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE tenant RENAME INDEX uniq_4e59c462989d9b62 TO uq_tenant_slug');
        $this->addSql('ALTER TABLE tenant RENAME INDEX uniq_4e59c462628de0d9 TO uq_tenant_db_name');
        $this->addSql('ALTER TABLE user RENAME INDEX idx_8d93d6499033212a TO fk_user_tenant');
    }
}
