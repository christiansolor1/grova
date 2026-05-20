<?php

declare(strict_types=1);

namespace App\Service\Respaldo;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Respaldo del módulo Work.
 *
 * Tablas: work_client, work_holiday, work_vacation, work_day, work_invoice, work_invoice_bonus_line
 * Archivos: comprobantes de pago en var/work_invoice_payment_proofs/
 */
#[AutoconfigureTag('app.respaldo.modulo')]
final class RespaldoWork implements RespaldoModuloInterface
{
    /** @var list<string> Orden de inserción (respetando FKs) */
    private const TABLAS = [
        'work_client',
        'work_holiday',
        'work_vacation',
        'work_day',
        'work_invoice',
        'work_invoice_bonus_line',
    ];

    /** @var list<string> Orden inverso para truncar (respetando FKs) */
    private const TABLAS_TRUNCATE = [
        'work_invoice_bonus_line',
        'work_invoice',
        'work_day',
        'work_vacation',
        'work_holiday',
        'work_client',
    ];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $projectDir,
    ) {
    }

    public function nombreModulo(): string
    {
        return 'work';
    }

    public function etiqueta(): string
    {
        return 'Work — Días trabajados';
    }

    public function exportar(): array
    {
        $conn = $this->getConnection();
        $data = [];

        foreach (self::TABLAS as $tabla) {
            $rows = $conn->fetchAllAssociative(sprintf('SELECT * FROM %s', $tabla));
            /** @var list<array<string, mixed>> $rows */
            $data[$tabla] = $rows;
        }

        /** @var list<array{nombre: string, original: string, mime: string}> $archivos */
        $archivos = $this->listarArchivos();

        return [
            'data' => $data,
            'archivos' => $archivos,
        ];
    }

    /**
     * @param array<string, list<array<string, mixed>>> $data
     */
    public function importar(array $data, string $dir): void
    {
        $conn = $this->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // Truncar en orden inverso
            foreach (self::TABLAS_TRUNCATE as $tabla) {
                $conn->executeStatement(sprintf('TRUNCATE TABLE %s', $tabla));
            }

            // Insertar en orden directo
            foreach (self::TABLAS as $tabla) {
                $rows = $data[$tabla] ?? [];
                if ($rows === []) {
                    continue;
                }

                foreach ($rows as $row) {
                    $conn->insert($tabla, $row);
                }
            }

            // Restaurar archivos
            $this->restaurarArchivos($conn, $dir);
        } finally {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    private function getConnection(): Connection
    {
        $conn = $this->doctrine->getConnection('tenant');
        if (!$conn instanceof Connection) {
            throw new \RuntimeException('No se pudo obtener la conexión tenant.');
        }

        return $conn;
    }

    /**
     * @return list<array{nombre: string, original: string, mime: string}>
     */
    private function listarArchivos(): array
    {
        $dir = $this->projectDir . '/var/work_invoice_payment_proofs';
        if (!is_dir($dir)) {
            return [];
        }

        $conn = $this->getConnection();
        /** @var list<array{payment_proof_stored_filename: string, payment_proof_original_name: string|null, payment_proof_mime: string|null}> $invoices */
        $invoices = $conn->fetchAllAssociative(
            'SELECT payment_proof_stored_filename, payment_proof_original_name, payment_proof_mime FROM work_invoice WHERE payment_proof_stored_filename IS NOT NULL'
        );

        $archivos = [];
        foreach ($invoices as $inv) {
            $nombre = (string) $inv['payment_proof_stored_filename'];
            $path = $dir . '/' . $nombre;
            if (!is_file($path)) {
                continue;
            }

            $archivos[] = [
                'nombre' => $nombre,
                'original' => (string) ($inv['payment_proof_original_name'] ?? $nombre),
                'mime' => (string) ($inv['payment_proof_mime'] ?? 'application/octet-stream'),
            ];
        }

        return $archivos;
    }

    private function restaurarArchivos(Connection $conn, string $tmpDir): void
    {
        $dir = $this->projectDir . '/var/work_invoice_payment_proofs';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return; // No se pudo crear el directorio, pero no es fatal
        }

        $filesDir = $tmpDir . '/files';
        if (!is_dir($filesDir)) {
            return;
        }

        $dh = opendir($filesDir);
        if ($dh === false) {
            return;
        }

        while (($entry = readdir($dh)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $src = $filesDir . '/' . $entry;
            if (!is_file($src)) {
                continue;
            }
            copy($src, $dir . '/' . $entry);
        }

        closedir($dh);
    }
}
