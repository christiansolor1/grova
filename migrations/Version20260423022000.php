<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423022000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add menu.status field (pendiente/hecho) with safe defaults';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE menu ADD status VARCHAR(20) NOT NULL DEFAULT 'pendiente'");
        // Mantener el comportamiento actual: lo ya existente se considera implementado.
        $this->addSql("UPDATE menu SET status = 'hecho'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE menu DROP status');
    }
}
