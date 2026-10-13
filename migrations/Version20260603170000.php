<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega tarjeta_digitos a wallet_cuenta y amplía numero_cuenta para encriptación';
    }

    public function up(Schema $schema): void
    {
        // wallet_cuenta: ampliar numero_cuenta + agregar tarjeta_digitos (encriptados)
        $this->addSql("ALTER TABLE wallet_cuenta MODIFY COLUMN numero_cuenta VARCHAR(200) DEFAULT NULL");
        $this->addSql("ALTER TABLE wallet_cuenta ADD COLUMN tarjeta_digitos VARCHAR(200) DEFAULT NULL AFTER numero_cuenta");
        // work_client: ampliar banc_swift y banc_cuenta para encriptación
        $this->addSql("ALTER TABLE work_client MODIFY COLUMN banc_swift VARCHAR(200) DEFAULT NULL");
        $this->addSql("ALTER TABLE work_client MODIFY COLUMN banc_cuenta VARCHAR(200) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wallet_cuenta DROP COLUMN tarjeta_digitos");
        $this->addSql("ALTER TABLE wallet_cuenta MODIFY COLUMN numero_cuenta VARCHAR(30) DEFAULT NULL");
        $this->addSql("ALTER TABLE work_client MODIFY COLUMN banc_swift VARCHAR(20) DEFAULT NULL");
        $this->addSql("ALTER TABLE work_client MODIFY COLUMN banc_cuenta VARCHAR(50) DEFAULT NULL");
    }
}
