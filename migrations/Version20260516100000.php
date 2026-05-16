<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Reparación: columnas FX / recibo_swift en work_invoice por esquema (idempotente).
 * Corrige BDs donde existían tasas sin recibo_swift u otra columna parcial.
 */
final class Version20260516100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: asegurar columnas recibo_swift y tasas en work_invoice (multi-schema, idempotente)';
    }

    public function up(Schema $schema): void
    {
        /** @var array<string, string> */
        $cols = [
            'recibo_swift' => 'INT DEFAULT NULL',
            'tasa_emision_eur_l' => 'DECIMAL(14,5) DEFAULT NULL',
            'tasa_emision_usd_l' => 'DECIMAL(14,5) DEFAULT NULL',
            'tasa_emision_fecha' => 'VARCHAR(16) DEFAULT NULL',
            'tasa_emision_source' => 'VARCHAR(64) DEFAULT NULL',
            'tasa_pago_eur_l' => 'DECIMAL(14,5) DEFAULT NULL',
            'tasa_pago_usd_l' => 'DECIMAL(14,5) DEFAULT NULL',
            'tasa_pago_fecha' => 'VARCHAR(16) DEFAULT NULL',
            'tasa_pago_source' => 'VARCHAR(64) DEFAULT NULL',
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
        // Sin reversa: migración de reparación de esquema; down de Version20260515130000 cubre el borrado conjunto.
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
