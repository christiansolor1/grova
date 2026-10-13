<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renombra ultimos_digitos → numero_cuenta (VARCHAR 30) en wallet_cuenta';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_cuenta CHANGE ultimos_digitos numero_cuenta VARCHAR(30) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_cuenta CHANGE numero_cuenta ultimos_digitos VARCHAR(4) DEFAULT NULL");
    }
}
