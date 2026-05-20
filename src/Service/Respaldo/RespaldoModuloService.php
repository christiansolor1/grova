<?php

declare(strict_types=1);

namespace App\Service\Respaldo;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Orquestador de respaldos por módulo.
 *
 * Genera un ZIP con data.json + files/ y procesa la subida para restaurar.
 */
final class RespaldoModuloService
{
    /** @var array<string, RespaldoModuloInterface> */
    private array $modulos = [];

    /**
     * @param RespaldoModuloInterface[] $modulos
     */
    public function __construct(
        private readonly string $projectDir,
        #[TaggedIterator('app.respaldo.modulo')]
        iterable $modulos,
    ) {
        foreach ($modulos as $modulo) {
            $this->modulos[$modulo->nombreModulo()] = $modulo;
        }
    }

    /**
     * @return RespaldoModuloInterface[]
     */
    public function getModulos(): array
    {
        return array_values($this->modulos);
    }

    public function getModulo(string $nombre): ?RespaldoModuloInterface
    {
        return $this->modulos[$nombre] ?? null;
    }

    /**
     * Genera un ZIP de respaldo y lo devuelve como descarga.
     */
    public function generarRespaldo(string $nombreModulo): BinaryFileResponse
    {
        $modulo = $this->getModulo($nombreModulo);
        if ($modulo === null) {
            throw new \InvalidArgumentException(sprintf('Módulo "%s" no soportado.', $nombreModulo));
        }

        $exportado = $modulo->exportar();

        $tmpDir = $this->projectDir . '/var/respaldos_tmp/' . bin2hex(random_bytes(8));
        if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new \RuntimeException('No se pudo crear el directorio temporal.');
        }

        try {
            // Escribir data.json
            $payload = [
                'modulo' => $nombreModulo,
                'version' => 1,
                'exportado' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'data' => $exportado['data'],
                'archivos' => $exportado['archivos'],
            ];

            file_put_contents(
                $tmpDir . '/data.json',
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            );

            // Copiar archivos a files/
            $filesDir = $tmpDir . '/files';
            if ($exportado['archivos'] !== []) {
                if (!is_dir($filesDir) && !@mkdir($filesDir, 0775, true) && !is_dir($filesDir)) {
                    throw new \RuntimeException('No se pudo crear el directorio de archivos.');
                }

                $proofsDir = $this->projectDir . '/var/work_invoice_payment_proofs';
                foreach ($exportado['archivos'] as $archivo) {
                    $src = $proofsDir . '/' . $archivo['nombre'];
                    if (is_file($src)) {
                        copy($src, $filesDir . '/' . $archivo['nombre']);
                    }
                }
            }

            // Crear ZIP
            $zipPath = $tmpDir . '/' . $nombreModulo . '_respaldo_' . date('Ymd_His') . '.zip';
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
                throw new \RuntimeException('No se pudo crear el archivo ZIP.');
            }

            $zip->addFile($tmpDir . '/data.json', 'data.json');

            if (is_dir($filesDir)) {
                $dh = opendir($filesDir);
                if ($dh !== false) {
                    while (($entry = readdir($dh)) !== false) {
                        if ($entry === '.' || $entry === '..') {
                            continue;
                        }
                        $zip->addFile($filesDir . '/' . $entry, 'files/' . $entry);
                    }
                    closedir($dh);
                }
            }

            $zip->close();

            $response = new BinaryFileResponse($zipPath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($zipPath)
            );
            $response->deleteFileAfterSend(true);

            return $response;
        } finally {
            // Limpiar tmp (excepto el ZIP que se auto-borra al enviarse)
            if (isset($filesDir) && is_dir($filesDir)) {
                $this->rmdirRecursive($filesDir);
            }
            $jsonPath = $tmpDir . '/data.json';
            if (is_file($jsonPath)) {
                @unlink($jsonPath);
            }
        }
    }

    /**
     * Procesa un ZIP subido y restaura los datos.
     *
     * @param string      $zipPath    Ruta al archivo ZIP subido
     * @param string|null $nombreModulo Forzar módulo (opcional, se lee del JSON)
     */
    public function procesarRespaldo(string $zipPath, ?string $nombreModulo = null): string
    {
        $tmpDir = $this->projectDir . '/var/respaldos_tmp/' . bin2hex(random_bytes(8));
        if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new \RuntimeException('No se pudo crear el directorio temporal.');
        }

        try {
            $zip = new \ZipArchive();
            $res = $zip->open($zipPath);
            if ($res !== true) {
                throw new \InvalidArgumentException('No se pudo abrir el archivo ZIP.');
            }

            $zip->extractTo($tmpDir);
            $zip->close();

            // Leer data.json
            $jsonPath = $tmpDir . '/data.json';
            if (!is_file($jsonPath)) {
                throw new \InvalidArgumentException('El ZIP no contiene data.json.');
            }

            /** @var array{modulo: string, version: int, exportado: string, data: array<string, list<array<string, mixed>>>, archivos: list<array{nombre: string, original: string, mime: string}>} $payload */
            $payload = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($payload['modulo'], $payload['data'])) {
                throw new \InvalidArgumentException('data.json no tiene la estructura esperada.');
            }

            $moduloKey = $nombreModulo ?? $payload['modulo'];
            $modulo = $this->getModulo($moduloKey);
            if ($modulo === null) {
                throw new \InvalidArgumentException(sprintf('Módulo "%s" no soportado.', $moduloKey));
            }

            $modulo->importar($payload['data'], $tmpDir);

            return sprintf('Respaldo de "%s" restaurado correctamente.', $modulo->etiqueta());
        } finally {
            $this->rmdirRecursive($tmpDir);
        }
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $dh = opendir($dir);
        if ($dh === false) {
            return;
        }

        while (($entry = readdir($dh)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->rmdirRecursive($path);
            } else {
                @unlink($path);
            }
        }

        closedir($dh);
        @rmdir($dir);
    }
}
