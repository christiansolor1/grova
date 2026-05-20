<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518232241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_credencial_biometrica (id INT AUTO_INCREMENT NOT NULL, credential_id LONGTEXT NOT NULL, public_key LONGTEXT NOT NULL, nombre_dispositivo VARCHAR(100) DEFAULT NULL, creado_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ultimo_uso_en DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_id INT NOT NULL, INDEX IDX_BF2386C2A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_credencial_biometrica ADD CONSTRAINT FK_BF2386C2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // Migrar credenciales existentes de user_lock a la nueva tabla
        $this->addSql("
            INSERT INTO user_credencial_biometrica (user_id, credential_id, public_key, nombre_dispositivo, creado_en)
            SELECT ul.user_id, ul.webauthn_credential_id, ul.webauthn_public_key, 'Dispositivo principal', NOW()
            FROM user_lock ul
            WHERE ul.webauthn_enabled = 1
              AND ul.webauthn_credential_id IS NOT NULL
              AND ul.webauthn_public_key IS NOT NULL
        ");

        $this->addSql('ALTER TABLE user_lock DROP webauthn_credential_id, DROP webauthn_public_key, DROP webauthn_enabled');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_credencial_biometrica DROP FOREIGN KEY FK_BF2386C2A76ED395');
        $this->addSql('DROP TABLE user_credencial_biometrica');
        $this->addSql('ALTER TABLE user_lock ADD webauthn_credential_id LONGTEXT DEFAULT NULL, ADD webauthn_public_key LONGTEXT DEFAULT NULL, ADD webauthn_enabled TINYINT NOT NULL');
    }
}
