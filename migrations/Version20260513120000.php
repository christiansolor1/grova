<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Añade comision_banco_hnl en cada esquema que tenga work_invoice (no solo grova_christian).
 */
final class Version20260513120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: comision_banco_hnl en todos los esquemas con tabla work_invoice';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->schemasWithWorkInvoiceMissingColumn() as $db) {
            $this->addSql(sprintf(
                'ALTER TABLE `%s`.`work_invoice` ADD COLUMN comision_banco_hnl DECIMAL(10,2) DEFAULT NULL AFTER pagada_at',
                $this->quoteDbName($db),
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->schemasWithColumn() as $db) {
            $this->addSql(sprintf(
                'ALTER TABLE `%s`.`work_invoice` DROP COLUMN comision_banco_hnl',
                $this->quoteDbName($db),
            ));
        }
    }

    /**
     * @return list<string>
     */
    private function schemasWithWorkInvoiceMissingColumn(): array
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
                    AND c.column_name = 'comision_banco_hnl'
              )
            SQL,
        );

        return array_values(array_map(static fn (array $r): string => (string) $r['dbname'], $rows));
    }

    /**
     * @return list<string>
     */
    private function schemasWithColumn(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
            SELECT DISTINCT table_schema AS dbname
            FROM information_schema.columns
            WHERE table_name = 'work_invoice'
              AND column_name = 'comision_banco_hnl'
            SQL,
        );

        return array_values(array_map(static fn (array $r): string => (string) $r['dbname'], $rows));
    }

    private function quoteDbName(string $db): string
    {
        return str_replace('`', '``', $db);
    }
}
