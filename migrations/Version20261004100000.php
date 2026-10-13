<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004100000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE work_day ADD es_permiso TINYINT(1) NOT NULL DEFAULT 0, ADD es_incapacidad TINYINT(1) NOT NULL DEFAULT 0, ADD documento_stored_filename VARCHAR(180) DEFAULT NULL, ADD documento_original_name VARCHAR(255) DEFAULT NULL, ADD documento_mime VARCHAR(127) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE work_day DROP es_permiso, DROP es_incapacidad, DROP documento_stored_filename, DROP documento_original_name, DROP documento_mime");
    }
}
