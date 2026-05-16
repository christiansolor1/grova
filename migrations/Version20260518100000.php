<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * work_invoice: tasas inferidas (L/€ = L÷EUR, L/US$ = L÷US$ de la grilla) solo donde faltan; cobro copia emisión si pagada.
 */
final class Version20260518100000 extends AbstractMigration
{
    private const INFERRED_EUR_L = '29.28020';

    private const INFERRED_USD_L = '26.62325';

    public function getDescription(): string
    {
        return 'Work: backfill tasa_emision / tasa_pago en work_invoice (tasas inferidas de la grilla L/US$)';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->schemasWithWorkInvoice() as $db) {
            if (!$this->schemaHasColumn($db, 'tasa_emision_eur_l')) {
                continue;
            }
            $q = $this->quoteId($db);
            $t = $this->quoteId('work_invoice');
            $this->addSql(sprintf(
                'UPDATE %s.%s SET
                    tasa_emision_eur_l = %s,
                    tasa_emision_usd_l = %s,
                    tasa_emision_fecha = DATE_FORMAT(COALESCE(enviada_at, created_at), \'%%d/%%m/%%Y\'),
                    tasa_emision_source = %s
                 WHERE tasa_emision_eur_l IS NULL OR IFNULL(tasa_emision_eur_l, 0) = 0',
                $q,
                $t,
                $this->connection->quote(self::INFERRED_EUR_L),
                $this->connection->quote(self::INFERRED_USD_L),
                $this->connection->quote('inferred_grid_equiv_20260518'),
            ));
            $this->addSql(sprintf(
                'UPDATE %s.%s SET
                    tasa_pago_eur_l = tasa_emision_eur_l,
                    tasa_pago_usd_l = tasa_emision_usd_l,
                    tasa_pago_fecha = DATE_FORMAT(pagada_at, \'%%d/%%m/%%Y\'),
                    tasa_pago_source = %s
                 WHERE pagada_at IS NOT NULL
                   AND (tasa_pago_eur_l IS NULL OR IFNULL(tasa_pago_eur_l, 0) = 0)
                   AND tasa_emision_eur_l IS NOT NULL AND IFNULL(tasa_emision_eur_l, 0) > 0',
                $q,
                $t,
                $this->connection->quote('copy_emission_after_grid_infer'),
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->schemasWithWorkInvoice() as $db) {
            if (!$this->schemaHasColumn($db, 'tasa_emision_eur_l')) {
                continue;
            }
            $q = $this->quoteId($db);
            $t = $this->quoteId('work_invoice');
            $srcE = $this->connection->quote('inferred_grid_equiv_20260518');
            $srcP = $this->connection->quote('copy_emission_after_grid_infer');
            $this->addSql(sprintf(
                'UPDATE %s.%s SET
                    tasa_emision_eur_l = NULL,
                    tasa_emision_usd_l = NULL,
                    tasa_emision_fecha = NULL,
                    tasa_emision_source = NULL
                 WHERE tasa_emision_source = %s',
                $q,
                $t,
                $srcE,
            ));
            $this->addSql(sprintf(
                'UPDATE %s.%s SET
                    tasa_pago_eur_l = NULL,
                    tasa_pago_usd_l = NULL,
                    tasa_pago_fecha = NULL,
                    tasa_pago_source = NULL
                 WHERE tasa_pago_source = %s',
                $q,
                $t,
                $srcP,
            ));
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
