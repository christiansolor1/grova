<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'wallet_category: agregar parent_id para subcategorías (máx. 2 niveles)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_category ADD COLUMN parent_id INT NULL AFTER color');
        $this->addSql('ALTER TABLE wallet_category ADD CONSTRAINT fk_wcat_parent FOREIGN KEY (parent_id) REFERENCES wallet_category(id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_category DROP FOREIGN KEY fk_wcat_parent');
        $this->addSql('ALTER TABLE wallet_category DROP COLUMN parent_id');
    }
}
