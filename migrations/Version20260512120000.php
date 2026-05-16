<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: comisión banco real por factura (work_invoice.comision_banco_hnl)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('USE grova_christian');

        $this->addSql('ALTER TABLE work_invoice ADD COLUMN IF NOT EXISTS comision_banco_hnl DECIMAL(10,2) DEFAULT NULL AFTER pagada_at');

        $this->addSql('USE grova');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('USE grova_christian');

        $this->addSql('ALTER TABLE work_invoice DROP COLUMN IF EXISTS comision_banco_hnl');

        $this->addSql('USE grova');
    }
}
