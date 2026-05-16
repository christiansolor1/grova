<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Bonos extra como varias filas (work_invoice_bonus_line) y eliminación de columnas planas en work_invoice.
 */
final class Version20260515203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: tabla work_invoice_bonus_line, migrar bono_manual_*, quitar columnas en work_invoice';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->schemasWithWorkInvoice() as $db) {
            $q = $this->quoteDbName($db);

            if (!$this->tableExists($db, 'work_invoice_bonus_line')) {
                $this->addSql(sprintf(
                    <<<'SQL'
                    CREATE TABLE `%s`.`work_invoice_bonus_line` (
                        id INT AUTO_INCREMENT NOT NULL,
                        invoice_id INT NOT NULL,
                        sort_order INT NOT NULL DEFAULT 0,
                        importe_eur DECIMAL(10,2) NOT NULL DEFAULT 0,
                        concepto VARCHAR(255) DEFAULT NULL,
                        PRIMARY KEY(id),
                        INDEX idx_wibl_invoice (invoice_id),
                        CONSTRAINT fk_wibl_invoice FOREIGN KEY (invoice_id) REFERENCES `%s`.`work_invoice` (id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    SQL,
                    $q,
                    $q,
                ));
            }

            if ($this->columnExists($db, 'work_invoice', 'bono_manual_eur')) {
                $this->addSql(sprintf(
                    <<<'SQL'
                    INSERT INTO `%s`.`work_invoice_bonus_line` (invoice_id, sort_order, importe_eur, concepto)
                    SELECT wi.id, 0, wi.bono_manual_eur, wi.bono_manual_concepto
                    FROM `%s`.`work_invoice` wi
                    WHERE CAST(wi.bono_manual_eur AS DECIMAL(12,2)) > 0
                      AND NOT EXISTS (
                          SELECT 1 FROM `%s`.`work_invoice_bonus_line` bl WHERE bl.invoice_id = wi.id
                      )
                    SQL,
                    $q,
                    $q,
                    $q,
                ));

                $this->addSql(sprintf(
                    'ALTER TABLE `%s`.`work_invoice` DROP COLUMN bono_manual_concepto, DROP COLUMN bono_manual_eur',
                    $q,
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->schemasWithWorkInvoice() as $db) {
            $q = $this->quoteDbName($db);

            if (!$this->tableExists($db, 'work_invoice')) {
                continue;
            }

            if (!$this->columnExists($db, 'work_invoice', 'bono_manual_eur')) {
                $this->addSql(sprintf(
                    'ALTER TABLE `%s`.`work_invoice` ADD COLUMN bono_manual_eur DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER monto_bonus, ADD COLUMN bono_manual_concepto VARCHAR(255) DEFAULT NULL AFTER bono_manual_eur',
                    $q,
                ));
            }

            if ($this->tableExists($db, 'work_invoice_bonus_line')) {
                $this->addSql(sprintf(
                    <<<'SQL'
                    UPDATE `%s`.`work_invoice` wi
                    SET wi.bono_manual_eur = COALESCE((
                        SELECT SUM(CAST(bl.importe_eur AS DECIMAL(12,2)))
                        FROM `%s`.`work_invoice_bonus_line` bl
                        WHERE bl.invoice_id = wi.id
                    ), 0)
                    SQL,
                    $q,
                    $q,
                ));
                $this->addSql(sprintf(
                    <<<'SQL'
                    UPDATE `%s`.`work_invoice` wi
                    SET wi.bono_manual_concepto = (
                        SELECT NULLIF(TRIM(BOTH ' | ' FROM GROUP_CONCAT(COALESCE(NULLIF(TRIM(bl.concepto), ''), '') ORDER BY bl.sort_order, bl.id SEPARATOR ' | ')), '')
                        FROM `%s`.`work_invoice_bonus_line` bl
                        WHERE bl.invoice_id = wi.id
                    )
                    SQL,
                    $q,
                    $q,
                ));

                $this->addSql(sprintf('DROP TABLE IF EXISTS `%s`.`work_invoice_bonus_line`', $q));
            }
        }
    }

    /**
     * @return list<string>
     */
    private function schemasWithWorkInvoice(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
            SELECT DISTINCT table_schema AS dbname
            FROM information_schema.tables
            WHERE table_name = 'work_invoice'
              AND table_type = 'BASE TABLE'
              AND table_schema NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
            SQL,
        );

        return array_values(array_map(static fn (array $r): string => (string) $r['dbname'], $rows));
    }

    private function tableExists(string $schema, string $table): bool
    {
        $n = (int) $this->connection->fetchOne(
            <<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = ? AND table_name = ? AND table_type = 'BASE TABLE'
            SQL,
            [$schema, $table],
        );

        return $n > 0;
    }

    private function columnExists(string $schema, string $table, string $column): bool
    {
        $n = (int) $this->connection->fetchOne(
            <<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ? AND column_name = ?
            SQL,
            [$schema, $table, $column],
        );

        return $n > 0;
    }

    private function quoteDbName(string $db): string
    {
        return str_replace('`', '``', $db);
    }
}
