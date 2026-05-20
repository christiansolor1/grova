<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517232635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE licencia (id INT AUTO_INCREMENT NOT NULL, clave LONGTEXT NOT NULL, estado VARCHAR(20) NOT NULL, fecha_emision DATETIME NOT NULL, fecha_vencimiento DATETIME NOT NULL, duracion_dias INT NOT NULL, modulos JSON NOT NULL, notas LONGTEXT DEFAULT NULL, tenant_id INT NOT NULL, INDEX IDX_3C18920B9033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE licencia ADD CONSTRAINT FK_3C18920B9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licencia DROP FOREIGN KEY FK_3C18920B9033212A');
        $this->addSql('DROP TABLE licencia');
    }
}
