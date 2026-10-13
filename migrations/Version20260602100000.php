<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'work_invoice: agrega columnas de comprobante banco (bank_proof_stored_filename, bank_proof_original_name, bank_proof_mime)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_invoice
            ADD COLUMN bank_proof_stored_filename VARCHAR(180) DEFAULT NULL,
            ADD COLUMN bank_proof_original_name VARCHAR(255) DEFAULT NULL,
            ADD COLUMN bank_proof_mime VARCHAR(127) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_invoice
            DROP COLUMN bank_proof_stored_filename,
            DROP COLUMN bank_proof_original_name,
            DROP COLUMN bank_proof_mime
        ');
    }
}
