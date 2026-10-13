<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Módulo Residencial — crea las 9 tablas del sistema de gestión
 * de colonias residenciales cerradas.
 */
final class Version20260524100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Módulo Residencial: tablas res_residencial, res_manzana, res_casa, res_persona, res_visitante, res_pase, res_registro_entrada, res_paquete, res_incidente';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE res_residencial (
                id               INT AUTO_INCREMENT NOT NULL,
                tenant_id        INT NOT NULL,
                nombre           VARCHAR(255) NOT NULL,
                direccion        LONGTEXT DEFAULT NULL,
                logo             VARCHAR(255) DEFAULT NULL,
                telefono         VARCHAR(30) DEFAULT NULL,
                cantidad_casas   INT DEFAULT NULL,
                coordenadas_lat  DECIMAL(10,7) DEFAULT NULL,
                coordenadas_lng  DECIMAL(10,7) DEFAULT NULL,
                estado           VARCHAR(20) NOT NULL DEFAULT "activa",
                mapa_json        LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)",
                created_at       DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
                INDEX idx_res_residencial_tenant_estado (tenant_id, estado),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_manzana (
                id             INT AUTO_INCREMENT NOT NULL,
                tenant_id      INT NOT NULL,
                residencial_id INT NOT NULL,
                nombre         VARCHAR(100) NOT NULL,
                posicion_x     DOUBLE PRECISION DEFAULT NULL,
                posicion_y     DOUBLE PRECISION DEFAULT NULL,
                INDEX idx_res_manzana_tenant (tenant_id, residencial_id),
                INDEX IDX_res_manzana_residencial (residencial_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_casa (
                id             INT AUTO_INCREMENT NOT NULL,
                tenant_id      INT NOT NULL,
                residencial_id INT NOT NULL,
                manzana_id     INT DEFAULT NULL,
                numero         VARCHAR(20) NOT NULL,
                tipo           VARCHAR(20) NOT NULL DEFAULT "casa",
                posicion_x     DOUBLE PRECISION DEFAULT NULL,
                posicion_y     DOUBLE PRECISION DEFAULT NULL,
                estado         VARCHAR(20) NOT NULL DEFAULT "vacia",
                INDEX idx_res_casa_tenant (tenant_id, residencial_id),
                INDEX idx_res_casa_estado (estado),
                INDEX IDX_res_casa_residencial (residencial_id),
                INDEX IDX_res_casa_manzana (manzana_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_persona (
                id              INT AUTO_INCREMENT NOT NULL,
                tenant_id       INT NOT NULL,
                casa_id         INT DEFAULT NULL,
                nombre_completo VARCHAR(255) NOT NULL,
                dni             VARCHAR(30) DEFAULT NULL,
                telefono        VARCHAR(30) DEFAULT NULL,
                email           VARCHAR(180) DEFAULT NULL,
                tipo            VARCHAR(30) NOT NULL DEFAULT "propietario",
                foto            VARCHAR(255) DEFAULT NULL,
                estado          VARCHAR(20) NOT NULL DEFAULT "activo",
                user_id         INT DEFAULT NULL,
                created_at      DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
                INDEX idx_res_persona_tenant_tipo (tenant_id, tipo),
                INDEX idx_res_persona_user (user_id),
                INDEX IDX_res_persona_casa (casa_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_visitante (
                id              INT AUTO_INCREMENT NOT NULL,
                tenant_id       INT NOT NULL,
                nombre_completo VARCHAR(255) NOT NULL,
                dni             VARCHAR(30) DEFAULT NULL,
                telefono        VARCHAR(30) DEFAULT NULL,
                foto            VARCHAR(255) DEFAULT NULL,
                creado_en       DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
                INDEX idx_res_visitante_tenant_dni (tenant_id, dni),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_pase (
                id             INT AUTO_INCREMENT NOT NULL,
                tenant_id      INT NOT NULL,
                residente_id   INT NOT NULL,
                visitante_id   INT DEFAULT NULL,
                casa_destino_id INT NOT NULL,
                qr_token       VARCHAR(36) NOT NULL,
                tipo           VARCHAR(20) NOT NULL DEFAULT "unica_vez",
                fecha_inicio   DATE DEFAULT NULL COMMENT "(DC2Type:date_immutable)",
                fecha_fin      DATE DEFAULT NULL COMMENT "(DC2Type:date_immutable)",
                hora_inicio    VARCHAR(5) DEFAULT NULL,
                hora_fin       VARCHAR(5) DEFAULT NULL,
                dias_semana    LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)",
                estado         VARCHAR(20) NOT NULL DEFAULT "pendiente",
                creado_en      DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
                UNIQUE INDEX UNIQ_res_pase_token (qr_token),
                INDEX idx_res_pase_tenant_estado (tenant_id, estado),
                INDEX IDX_res_pase_residente (residente_id),
                INDEX IDX_res_pase_visitante (visitante_id),
                INDEX IDX_res_pase_casa_destino (casa_destino_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_registro_entrada (
                id           INT AUTO_INCREMENT NOT NULL,
                tenant_id    INT NOT NULL,
                pase_id      INT DEFAULT NULL,
                visitante_id INT NOT NULL,
                guardia_id   INT NOT NULL,
                tipo_entrada VARCHAR(20) NOT NULL DEFAULT "qr",
                hora_entrada DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
                hora_salida  DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)",
                foto_entrada VARCHAR(255) DEFAULT NULL,
                observaciones LONGTEXT DEFAULT NULL,
                es_primera_vez TINYINT(1) NOT NULL DEFAULT 0,
                INDEX idx_res_registro_tenant_hora (tenant_id, hora_entrada),
                INDEX IDX_res_registro_pase (pase_id),
                INDEX IDX_res_registro_visitante (visitante_id),
                INDEX IDX_res_registro_guardia (guardia_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_paquete (
                id                  INT AUTO_INCREMENT NOT NULL,
                tenant_id           INT NOT NULL,
                residente_id        INT NOT NULL,
                casa_id             INT NOT NULL,
                empresa_mensajeria  VARCHAR(100) DEFAULT NULL,
                descripcion         LONGTEXT DEFAULT NULL,
                foto                VARCHAR(255) DEFAULT NULL,
                estado              VARCHAR(20) NOT NULL DEFAULT "recibido",
                recibido_en         DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
                entregado_en        DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)",
                INDEX idx_res_paquete_tenant_estado (tenant_id, estado),
                INDEX IDX_res_paquete_residente (residente_id),
                INDEX IDX_res_paquete_casa (casa_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE res_incidente (
                id         INT AUTO_INCREMENT NOT NULL,
                tenant_id  INT NOT NULL,
                guardia_id INT NOT NULL,
                tipo       VARCHAR(20) NOT NULL DEFAULT "otro",
                descripcion LONGTEXT NOT NULL,
                foto       VARCHAR(255) DEFAULT NULL,
                creado_en  DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
                INDEX idx_res_incidente_tenant_tipo (tenant_id, tipo),
                INDEX IDX_res_incidente_guardia (guardia_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        // Foreign keys
        $this->addSql('ALTER TABLE res_manzana ADD CONSTRAINT FK_res_manzana_residencial FOREIGN KEY (residencial_id) REFERENCES res_residencial (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE res_casa ADD CONSTRAINT FK_res_casa_residencial FOREIGN KEY (residencial_id) REFERENCES res_residencial (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE res_casa ADD CONSTRAINT FK_res_casa_manzana FOREIGN KEY (manzana_id) REFERENCES res_manzana (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_persona ADD CONSTRAINT FK_res_persona_casa FOREIGN KEY (casa_id) REFERENCES res_casa (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_pase ADD CONSTRAINT FK_res_pase_residente FOREIGN KEY (residente_id) REFERENCES res_persona (id)');
        $this->addSql('ALTER TABLE res_pase ADD CONSTRAINT FK_res_pase_visitante FOREIGN KEY (visitante_id) REFERENCES res_visitante (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_pase ADD CONSTRAINT FK_res_pase_casa_destino FOREIGN KEY (casa_destino_id) REFERENCES res_casa (id)');
        $this->addSql('ALTER TABLE res_registro_entrada ADD CONSTRAINT FK_res_registro_pase FOREIGN KEY (pase_id) REFERENCES res_pase (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE res_registro_entrada ADD CONSTRAINT FK_res_registro_visitante FOREIGN KEY (visitante_id) REFERENCES res_visitante (id)');
        $this->addSql('ALTER TABLE res_registro_entrada ADD CONSTRAINT FK_res_registro_guardia FOREIGN KEY (guardia_id) REFERENCES res_persona (id)');
        $this->addSql('ALTER TABLE res_paquete ADD CONSTRAINT FK_res_paquete_residente FOREIGN KEY (residente_id) REFERENCES res_persona (id)');
        $this->addSql('ALTER TABLE res_paquete ADD CONSTRAINT FK_res_paquete_casa FOREIGN KEY (casa_id) REFERENCES res_casa (id)');
        $this->addSql('ALTER TABLE res_incidente ADD CONSTRAINT FK_res_incidente_guardia FOREIGN KEY (guardia_id) REFERENCES res_persona (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE res_incidente DROP FOREIGN KEY FK_res_incidente_guardia');
        $this->addSql('ALTER TABLE res_paquete DROP FOREIGN KEY FK_res_paquete_residente');
        $this->addSql('ALTER TABLE res_paquete DROP FOREIGN KEY FK_res_paquete_casa');
        $this->addSql('ALTER TABLE res_registro_entrada DROP FOREIGN KEY FK_res_registro_pase');
        $this->addSql('ALTER TABLE res_registro_entrada DROP FOREIGN KEY FK_res_registro_visitante');
        $this->addSql('ALTER TABLE res_registro_entrada DROP FOREIGN KEY FK_res_registro_guardia');
        $this->addSql('ALTER TABLE res_pase DROP FOREIGN KEY FK_res_pase_residente');
        $this->addSql('ALTER TABLE res_pase DROP FOREIGN KEY FK_res_pase_visitante');
        $this->addSql('ALTER TABLE res_pase DROP FOREIGN KEY FK_res_pase_casa_destino');
        $this->addSql('ALTER TABLE res_persona DROP FOREIGN KEY FK_res_persona_casa');
        $this->addSql('ALTER TABLE res_casa DROP FOREIGN KEY FK_res_casa_residencial');
        $this->addSql('ALTER TABLE res_casa DROP FOREIGN KEY FK_res_casa_manzana');
        $this->addSql('ALTER TABLE res_manzana DROP FOREIGN KEY FK_res_manzana_residencial');

        $this->addSql('DROP TABLE res_incidente');
        $this->addSql('DROP TABLE res_paquete');
        $this->addSql('DROP TABLE res_registro_entrada');
        $this->addSql('DROP TABLE res_pase');
        $this->addSql('DROP TABLE res_visitante');
        $this->addSql('DROP TABLE res_persona');
        $this->addSql('DROP TABLE res_casa');
        $this->addSql('DROP TABLE res_manzana');
        $this->addSql('DROP TABLE res_residencial');
    }
}
