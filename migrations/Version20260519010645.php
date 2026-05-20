<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519010645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE log_actividad (
              id INT AUTO_INCREMENT NOT NULL,
              accion VARCHAR(80) NOT NULL,
              email VARCHAR(255) DEFAULT NULL,
              ip VARCHAR(15) NOT NULL,
              user_agent VARCHAR(512) DEFAULT NULL,
              detalles JSON DEFAULT NULL,
              created_at DATETIME NOT NULL,
              usuario_id INT DEFAULT NULL,
              INDEX idx_log_usuario (usuario_id),
              INDEX idx_log_accion (accion),
              INDEX idx_log_created (created_at),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              log_actividad
            ADD
              CONSTRAINT FK_9BF02188DB38439E FOREIGN KEY (usuario_id) REFERENCES user (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            CHANGE
              webauthn2fa_enabled webauthn2fa_enabled TINYINT NOT NULL,
            CHANGE
              pin2fa_enabled pin2fa_enabled TINYINT NOT NULL,
            CHANGE
              totp2fa_enabled totp2fa_enabled TINYINT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_credencial_biometrica
            CHANGE
              creado_en creado_en DATETIME NOT NULL,
            CHANGE
              ultimo_uso_en ultimo_uso_en DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_actividad DROP FOREIGN KEY FK_9BF02188DB38439E');
        $this->addSql('DROP TABLE log_actividad');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            CHANGE
              webauthn2fa_enabled webauthn2fa_enabled TINYINT DEFAULT 1 NOT NULL,
            CHANGE
              pin2fa_enabled pin2fa_enabled TINYINT DEFAULT 1 NOT NULL,
            CHANGE
              totp2fa_enabled totp2fa_enabled TINYINT DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_credencial_biometrica
            CHANGE
              creado_en creado_en DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              ultimo_uso_en ultimo_uso_en DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }
}
