<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Valida licencias on-premise firmadas con sodium.
 *
 * Protecciones implementadas:
 *  1. Firma criptográfica — cualquier modificación al payload invalida la licencia.
 *  2. Anti-retroceso de reloj — si now < issued_at, el reloj fue retrocedido → rechazar.
 *  3. Expiración — si now > expires_at → rechazar.
 *  4. Consistencia de duración — expires_at - issued_at debe coincidir con duration_days ± 60s.
 *  5. Versión de payload — se rechaza cualquier versión desconocida.
 */
final class ValidadorLicencias
{
    /**
     * Margen de tolerancia en segundos para diferencias de reloj menores (NTP, etc.).
     * Permite hasta 5 minutos de desfase hacia adelante (issued_at ligeramente en el futuro).
     * No permite retrocesos: si el reloj va hacia atrás más de este margen, se rechaza.
     */
    private const MARGEN_SEGUNDOS = 300;

    public function __construct(
        private readonly string $clavePublicaBase64,
    ) {
        if ($this->clavePublicaBase64 === '') {
            throw new \RuntimeException('LICENSE_PUBLIC_KEY no está configurado.');
        }
    }

    /**
     * @return array{
     *     valida: bool,
     *     motivo: string,
     *     payload: array<string, mixed>|null
     * }
     */
    public function validar(string $claveBase64): array
    {
        // 1. Decodificar
        $mensajeFirmado = base64_decode($claveBase64, true);
        if ($mensajeFirmado === false) {
            return $this->rechazar('Formato de licencia inválido.');
        }

        // 2. Verificar firma
        $clavePublica = base64_decode($this->clavePublicaBase64, true);
        if ($clavePublica === false || strlen($clavePublica) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $this->rechazar('Clave pública de validación inválida.');
        }

        $payloadJson = sodium_crypto_sign_open($mensajeFirmado, $clavePublica);
        if ($payloadJson === false) {
            return $this->rechazar('La firma de la licencia no es válida.');
        }

        // 3. Parsear payload
        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) $payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->rechazar('El contenido de la licencia está corrupto.');
        }

        // 4. Versión conocida
        if (($payload['v'] ?? null) !== 1) {
            return $this->rechazar('Versión de licencia no soportada.');
        }

        // 5. Campos obligatorios presentes
        foreach (['tenant', 'issued_at', 'expires_at', 'duration_days', 'modules'] as $campo) {
            if (!isset($payload[$campo])) {
                return $this->rechazar("Campo obligatorio ausente en la licencia: {$campo}.");
            }
        }

        $issuedAt  = (int) $payload['issued_at'];
        $expiresAt = (int) $payload['expires_at'];
        $duracion  = (int) $payload['duration_days'];
        $ahora     = time();

        // 6. Anti-retroceso de reloj: now no puede ser anterior a issued_at (con margen)
        if ($ahora < ($issuedAt - self::MARGEN_SEGUNDOS)) {
            return $this->rechazar('El reloj del servidor está retrocedido. La licencia no puede validarse.');
        }

        // 7. Licencia expirada
        if ($ahora > $expiresAt) {
            return $this->rechazar('La licencia ha expirado.');
        }

        // 8. Consistencia de duración — evita payloads manipulados donde alguien
        //    extiende expires_at sin poder firmar de nuevo
        $duracionEsperadaSegundos = $duracion * 86400;
        $duracionRealSegundos = $expiresAt - $issuedAt;
        if (abs($duracionRealSegundos - $duracionEsperadaSegundos) > 60) {
            return $this->rechazar('La duración de la licencia no es consistente.');
        }

        return [
            'valida'  => true,
            'motivo'  => '',
            'payload' => $payload,
        ];
    }

    public function esValida(string $claveBase64): bool
    {
        return $this->validar($claveBase64)['valida'];
    }

    /**
     * @return array{valida: bool, motivo: string, payload: null}
     */
    private function rechazar(string $motivo): array
    {
        return ['valida' => false, 'motivo' => $motivo, 'payload' => null];
    }
}
