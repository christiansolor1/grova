<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega ultimos_digitos a wallet_cuenta';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_cuenta ADD COLUMN ultimos_digitos VARCHAR(4) DEFAULT NULL AFTER institucion");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_cuenta DROP COLUMN ultimos_digitos");
    }
}
