<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega token de recuperación de contraseña al usuario.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD reset_token VARCHAR(100) DEFAULT NULL");
        $this->addSql("ALTER TABLE user ADD reset_token_expira DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user DROP reset_token");
        $this->addSql("ALTER TABLE user DROP reset_token_expira");
    }
}
