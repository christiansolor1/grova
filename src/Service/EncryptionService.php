<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Encriptación reversible AES-256-CBC para datos sensibles (números de cuenta, tarjetas).
 * La clave se deriva del APP_SECRET configurado en .env.
 */
final class EncryptionService
{
    private string $key;
    private const CIPHER = 'aes-256-cbc';

    public function __construct(string $appSecret)
    {
        // Derivamos una clave de 32 bytes desde el secret
        $this->key = substr(hash('sha256', $appSecret, true), 0, 32);
    }

    public function encrypt(string $value): string
    {
        $iv        = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encrypted = openssl_encrypt($value, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $encrypted): string
    {
        $data      = base64_decode($encrypted, true);
        $ivLen     = openssl_cipher_iv_length(self::CIPHER);
        $iv        = substr($data, 0, $ivLen);
        $ciphertext = substr($data, $ivLen);

        return (string) openssl_decrypt($ciphertext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);
    }

    public function isEncrypted(string $value): bool
    {
        // Los valores encriptados son base64 válido y decodifican a más de ivLen bytes
        $decoded = base64_decode($value, true);
        return $decoded !== false && strlen($decoded) > openssl_cipher_iv_length(self::CIPHER);
    }
}
