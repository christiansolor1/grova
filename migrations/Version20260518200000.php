<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega verificación de email al usuario (email_verificado, token, expiración).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD email_verificado TINYINT(1) NOT NULL DEFAULT 0");
        $this->addSql("ALTER TABLE user ADD token_verificacion VARCHAR(100) DEFAULT NULL");
        $this->addSql("ALTER TABLE user ADD token_verifica_expira DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user DROP email_verificado");
        $this->addSql("ALTER TABLE user DROP token_verificacion");
        $this->addSql("ALTER TABLE user DROP token_verifica_expira");
    }
}
