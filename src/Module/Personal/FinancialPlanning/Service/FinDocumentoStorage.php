<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Service;

use App\Module\Personal\FinancialPlanning\Entity\FinDocumento;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FinDocumentoStorage
{
    private const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly string $projectDir,
    ) {}

    public function baseDir(): string
    {
        return $this->projectDir . '/var/fin_documentos';
    }

    /**
     * Sube un archivo y rellena el FinDocumento. Devuelve mensaje de error o null si OK.
     */
    public function subir(FinDocumento $doc, UploadedFile $file): ?string
    {
        if (!$file->isValid()) {
            return 'Archivo inválido.';
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return 'El archivo supera los 10 MB.';
        }

        $mime = $this->detectarMime($file);
        if ($mime === null || !\in_array($mime, self::ALLOWED_MIMES, true)) {
            return 'Tipo de archivo no permitido. Solo PDF, JPG, PNG o WEBP.';
        }

        $ext = strtolower((string) $file->guessExtension());
        if ($ext === '' || $ext === 'jpeg') {
            $ext = match ($mime) {
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
                'image/webp'      => 'webp',
                default           => 'bin',
            };
        }

        $dir = $this->baseDir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return 'No se pudo crear el directorio de almacenamiento.';
        }

        $nombreAlmacenado = bin2hex(random_bytes(16)) . '.' . $ext;

        try {
            $file->move($dir, $nombreAlmacenado);
        } catch (\Throwable) {
            return 'Error al mover el archivo.';
        }

        if (!is_file($dir . '/' . $nombreAlmacenado)) {
            return 'El archivo no se guardó correctamente.';
        }

        $doc->setNombreOriginal($file->getClientOriginalName());
        $doc->setNombreAlmacenado($nombreAlmacenado);
        $doc->setTipoMime($mime);
        $doc->setTamanoBytes((int) filesize($dir . '/' . $nombreAlmacenado));

        return null;
    }

    public function eliminarArchivo(FinDocumento $doc): void
    {
        $ruta = $this->baseDir() . '/' . basename($doc->getNombreAlmacenado());
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    public function rutaAbsoluta(FinDocumento $doc): ?string
    {
        $ruta = $this->baseDir() . '/' . basename($doc->getNombreAlmacenado());
        return is_file($ruta) ? $ruta : null;
    }

    private function detectarMime(UploadedFile $file): ?string
    {
        $path = $file->getPathname();
        if (!is_file($path)) {
            return null;
        }
        $fi   = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($path);
        return \is_string($mime) ? $mime : null;
    }
}
