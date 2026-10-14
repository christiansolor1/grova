<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: pagada_at already included in work_invoice grova_core schema (no-op)';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}

