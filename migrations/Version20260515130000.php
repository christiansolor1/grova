<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tasas EUR/USD al generar y al cobrar; recibo SWIFT. Rellena comisiones/fechas conocidas (Christian / un solo cliente activo).
 */
final class Version20260515130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Work: tasas emisión/pago, recibo_swift + backfill comisiones SWIFT por mes facturado';
    }

    public function up(Schema $schema): void
    {
        /** @var array<string, string> nombre columna → fragmento SQL tras ADD COLUMN */
        $workInvoiceFxColumns = [
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

        $altered = [];
        foreach ($this->schemasWithWorkInvoice() as $db) {
            $q = $this->quoteId($db);
            $t = $this->quoteId('work_invoice');
            $addedAny = false;
            foreach ($workInvoiceFxColumns as $col => $ddl) {
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
                $addedAny = true;
            }
            if ($addedAny) {
                $altered[] = $db;
            }
        }

        $backfill = [
            ['anio' => 2026, 'mes' => 4, 'comision' => '737.45', 'recibo' => 31, 'pagada' => '2026-05-09 12:00:00'],
            ['anio' => 2026, 'mes' => 3, 'comision' => '734.94', 'recibo' => 30, 'pagada' => '2026-03-31 12:00:00'],
            ['anio' => 2026, 'mes' => 2, 'comision' => '743.07', 'recibo' => 29, 'pagada' => '2026-03-02 12:00:00'],
            ['anio' => 2026, 'mes' => 1, 'comision' => '746.53', 'recibo' => 28, 'pagada' => '2026-02-02 12:00:00'],
            ['anio' => 2025, 'mes' => 11, 'comision' => '741.25', 'recibo' => 27, 'pagada' => '2025-12-30 12:00:00'],
            ['anio' => 2025, 'mes' => 10, 'comision' => '740.65', 'recibo' => 26, 'pagada' => '2025-12-01 12:00:00'],
        ];

        foreach ($altered as $db) {
            $dq = $this->quoteId($db);
            $ti = $this->quoteId('work_invoice');
            $tc = $this->quoteId('work_client');
            foreach ($backfill as $row) {
                $this->addSql(sprintf(
                    'UPDATE %s.%s i INNER JOIN %s.%s c ON c.id = i.client_id AND c.activo = 1'
                    . ' SET i.comision_banco_hnl = %s, i.recibo_swift = %d, i.pagada_at = %s'
                    . ' WHERE i.anio = %d AND i.mes = %d AND i.pagada_at IS NOT NULL',
                    $dq,
                    $ti,
                    $dq,
                    $tc,
                    $this->connection->quote($row['comision']),
                    (int) $row['recibo'],
                    $this->connection->quote($row['pagada']),
                    (int) $row['anio'],
                    (int) $row['mes'],
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $cols = [
            'tasa_pago_source',
            'tasa_pago_fecha',
            'tasa_pago_usd_l',
            'tasa_pago_eur_l',
            'tasa_emision_source',
            'tasa_emision_fecha',
            'tasa_emision_usd_l',
            'tasa_emision_eur_l',
            'recibo_swift',
        ];
        foreach ($this->schemasWithTableHavingColumn('tasa_emision_eur_l') as $db) {
            $q = $this->quoteId($db);
            $t = $this->quoteId('work_invoice');
            foreach ($cols as $col) {
                $this->addSql(sprintf(
                    'ALTER TABLE %s.%s DROP COLUMN IF EXISTS %s',
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

    /** @return list<string> */
    private function schemasWithTableHavingColumn(string $column): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
            SELECT DISTINCT table_schema AS dbname
            FROM information_schema.columns
            WHERE table_name = 'work_invoice' AND column_name = ?
            SQL,
            [$column],
        );

        return array_values(array_map(static fn (array $r): string => (string) $r['dbname'], $rows));
    }

    private function quoteId(string $ident): string
    {
        return '`' . str_replace('`', '``', $ident) . '`';
    }
}
