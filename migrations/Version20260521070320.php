<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521070320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fishing_expense CHANGE monto monto DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE legal_payment CHANGE monto monto DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fishing_expense CHANGE monto monto NUMERIC(8, 2) NOT NULL');
        $this->addSql('ALTER TABLE legal_payment CHANGE monto monto NUMERIC(10, 2) NOT NULL');
    }
}
