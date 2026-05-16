<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed test users: alcides, jordi, pesca — password: grova2026';
    }

    public function up(Schema $schema): void
    {
        // Password: grova2026
        $hash = '$2y$13$GYmBp207s684/9vH66LgHeSzYRrDwZG2l.4/4yaglAgZWF0krNG/e';

        $this->addSql("USE grova");

        // Tenant IDs
        $this->addSql("
            INSERT IGNORE INTO user (username, email, password, roles, nombre, apellido, tenant_id)
            SELECT 'alcides', 'alcides@grova.com', :hash, '[\"ROLE_USER\"]', 'Alcides', 'Tenant',
                   (SELECT id FROM tenant WHERE slug = 'grova_alcides' LIMIT 1)
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM user WHERE email = 'alcides@grova.com')
        ", ['hash' => $hash]);

        $this->addSql("
            INSERT IGNORE INTO user (username, email, password, roles, nombre, apellido, tenant_id)
            SELECT 'jordi', 'jordi@grova.com', :hash, '[\"ROLE_USER\"]', 'Jordi', 'Tenant',
                   (SELECT id FROM tenant WHERE slug = 'grova_jordi' LIMIT 1)
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM user WHERE email = 'jordi@grova.com')
        ", ['hash' => $hash]);

        $this->addSql("
            INSERT IGNORE INTO user (username, email, password, roles, nombre, apellido, tenant_id)
            SELECT 'pesca', 'pesca@grova.com', :hash, '[\"ROLE_USER\"]', 'Grupo', 'Pesca',
                   (SELECT id FROM tenant WHERE slug = 'grova_pesca' LIMIT 1)
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM user WHERE email = 'pesca@grova.com')
        ", ['hash' => $hash]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user WHERE email IN ('alcides@grova.com','jordi@grova.com','pesca@grova.com')");
    }
}
