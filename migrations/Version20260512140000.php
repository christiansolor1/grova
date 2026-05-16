<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * work_invoice: comprobante de giro (PDF o imagen), un archivo por factura.
 */
final class Version20260512140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: payment_proof_* en work_invoice (comprobante PDF/imagen)';
    }

    public function up(Schema $schema): void
    {
        $cols = [
            'payment_proof_stored_filename' => 'VARCHAR(180) DEFAULT NULL',
            'payment_proof_original_name' => 'VARCHAR(255) DEFAULT NULL',
            'payment_proof_mime' => 'VARCHAR(127) DEFAULT NULL',
        ];

        foreach ($this->schemasWithWorkInvoice() as $db) {
            $q = $this->quoteId($db);
            $t = $this->quoteId('work_invoice');
            foreach ($cols as $col => $ddl) {
                if ($this->schemaHasColumn($db, $col)) {
                    continue;
                }
                $this->addSql(sprintf(
                    'ALTER TABLE %s.%s ADD COLUMN %s %s',
                    $q,
                    $t,
                    $this->quoteId($col),
                    $ddl,
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->schemasWithWorkInvoice() as $db) {
            $q = $this->quoteId($db);
            $t = $this->quoteId('work_invoice');
            foreach (['payment_proof_mime', 'payment_proof_original_name', 'payment_proof_stored_filename'] as $col) {
                if (!$this->schemaHasColumn($db, $col)) {
                    continue;
                }
                $this->addSql(sprintf(
                    'ALTER TABLE %s.%s DROP COLUMN %s',
                    $q,
                    $t,
                    $this->quoteId($col),
                ));
            }
        }
    }

    /** @return list<string> */
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

    private function schemaHasColumn(string $schema, string $column): bool
    {
        $n = (int) $this->connection->fetchOne(
            <<<'SQL'
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = ? AND table_name = 'work_invoice' AND column_name = ?
            SQL,
            [$schema, $column],
        );

        return $n > 0;
    }

    private function quoteId(string $ident): string
    {
        return '`' . str_replace('`', '``', $ident) . '`';
    }
}
