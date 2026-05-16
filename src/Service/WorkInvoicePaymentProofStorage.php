<?php

declare(strict_types=1);

namespace App\Service;

use App\Module\Personal\Work\Entity\WorkInvoice;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Guarda un único comprobante de giro (PDF o imagen) por factura Work, en var/work_invoice_payment_proofs/.
 */
final class WorkInvoicePaymentProofStorage
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function baseDir(): string
    {
        return $this->projectDir . '/var/work_invoice_payment_proofs';
    }

    /**
     * Sustituye el comprobante anterior si existe. Devuelve mensaje de error traducible (clave o texto) o null si OK / no hay archivo.
     */
    public function tryReplaceProof(WorkInvoice $invoice, ?UploadedFile $file): ?string
    {
        if ($file === null || $file->getClientOriginalName() === '') {
            return null;
        }

        if (!$file->isValid()) {
            return 'work.flash_payment_proof_upload_invalid';
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return 'work.flash_payment_proof_too_large';
        }

        $mime = $this->detectMime($file);
        if ($mime === null || !\in_array($mime, self::ALLOWED_MIMES, true)) {
            return 'work.flash_payment_proof_type_invalid';
        }

        $ext = strtolower((string) $file->guessExtension());
        if ($ext === '') {
            $ext = match ($mime) {
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'bin',
            };
        }

        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!\in_array($ext, $allowedExt, true)) {
            return 'work.flash_payment_proof_type_invalid';
        }

        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $dir = $this->baseDir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return 'work.flash_payment_proof_dir_error';
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $dir . '/' . $stored;

        try {
            $file->move($dir, $stored);
        } catch (\Throwable) {
            return 'work.flash_payment_proof_move_failed';
        }

        if (!is_file($target)) {
            return 'work.flash_payment_proof_move_failed';
        }

        $prev = $invoice->getPaymentProofStoredFilename();
        if ($prev !== null && $prev !== '') {
            $oldPath = $dir . '/' . basename($prev);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $invoice->setPaymentProofStoredFilename($stored);
        $invoice->setPaymentProofOriginalName($file->getClientOriginalName());
        $invoice->setPaymentProofMime($mime);

        return null;
    }

    public function removeProof(WorkInvoice $invoice): void
    {
        $this->removeStoredFileIfExists($invoice);
        $invoice->setPaymentProofStoredFilename(null);
        $invoice->setPaymentProofOriginalName(null);
        $invoice->setPaymentProofMime(null);
    }

    public function absolutePath(WorkInvoice $invoice): ?string
    {
        $fn = $invoice->getPaymentProofStoredFilename();
        if ($fn === null || $fn === '') {
            return null;
        }

        $path = $this->baseDir() . '/' . basename($fn);
        if (!is_file($path)) {
            return null;
        }

        return $path;
    }

    private function removeStoredFileIfExists(WorkInvoice $invoice): void
    {
        $fn = $invoice->getPaymentProofStoredFilename();
        if ($fn === null || $fn === '') {
            return;
        }

        $path = $this->baseDir() . '/' . basename($fn);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function detectMime(UploadedFile $file): ?string
    {
        $path = $file->getPathname();
        if (!is_file($path)) {
            return null;
        }

        $fi = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($path);

        return \is_string($mime) ? $mime : null;
    }
}
