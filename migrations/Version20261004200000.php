<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004200000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE agenda_evento (
            id INT AUTO_INCREMENT NOT NULL,
            tenant_id INT NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            fecha DATE NOT NULL,
            tipo VARCHAR(30) NOT NULL DEFAULT 'evento',
            recurrente TINYINT(1) NOT NULL DEFAULT 0,
            notas VARCHAR(500) DEFAULT NULL,
            color VARCHAR(20) DEFAULT NULL,
            notificar_correo TINYINT(1) NOT NULL DEFAULT 1,
            notificar_push TINYINT(1) NOT NULL DEFAULT 0,
            dias_anticipacion INT NOT NULL DEFAULT 1,
            creado_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_agenda_tenant (tenant_id),
            INDEX idx_agenda_fecha (fecha)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agenda_evento');
    }
}
