<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campo presentacion a wallet_gasto_fijo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_gasto_fijo ADD COLUMN presentacion VARCHAR(50) DEFAULT NULL AFTER unidad");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_gasto_fijo DROP COLUMN presentacion");
    }
}
