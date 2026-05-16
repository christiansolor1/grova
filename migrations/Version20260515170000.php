<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Bono manual en € por factura (aparte del bonus por puntualidad).
 */
final class Version20260515170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: bono_manual_eur + bono_manual_concepto en work_invoice (multi-schema)';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->schemasMissingBonoManualEur() as $db) {
            $q = $this->quoteDbName($db);
            $this->addSql(sprintf(
                'ALTER TABLE `%s`.`work_invoice` ADD COLUMN bono_manual_eur DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER monto_bonus, ADD COLUMN bono_manual_concepto VARCHAR(255) DEFAULT NULL AFTER bono_manual_eur',
                $q,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->schemasWithBonoManualEur() as $db) {
            $q = $this->quoteDbName($db);
            $this->addSql(sprintf(
                'ALTER TABLE `%s`.`work_invoice` DROP COLUMN bono_manual_concepto, DROP COLUMN bono_manual_eur',
                $q,
            ));
        }
    }

    /**
     * @return list<string>
     */
    private function schemasMissingBonoManualEur(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
            SELECT DISTINCT t.table_schema AS dbname
            FROM information_schema.tables t
            WHERE t.table_name = 'work_invoice'
              AND t.table_type = 'BASE TABLE'
              AND t.table_schema NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
              AND NOT EXISTS (
                  SELECT 1 FROM information_schema.columns c
                  WHERE c.table_schema = t.table_schema
                    AND c.table_name = 'work_invoice'
                    AND c.column_name = 'bono_manual_eur'
              )
            SQL,
        );

        return array_values(array_map(static fn (array $r): string => (string) $r['dbname'], $rows));
    }

    /**
     * @return list<string>
     */
    private function schemasWithBonoManualEur(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
            SELECT DISTINCT table_schema AS dbname
            FROM information_schema.columns
            WHERE table_name = 'work_invoice'
              AND column_name = 'bono_manual_eur'
            SQL,
        );

        return array_values(array_map(static fn (array $r): string => (string) $r['dbname'], $rows));
    }

    private function quoteDbName(string $db): string
    {
        return str_replace('`', '``', $db);
    }
}
