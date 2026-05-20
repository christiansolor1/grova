<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Licencia;
use App\Entity\Tenant;
use App\Repository\LicenciaRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Genera licencias firmadas con sodium para instalaciones on-premise.
 *
 * El payload incluye issued_at y expires_at como timestamps Unix.
 * Si el reloj del servidor on-premise está retrocedido, issued_at quedará
 * en el "futuro" y el ValidadorLicencias rechazará la licencia.
 */
final class GeneradorLicencias
{
    public function __construct(
        private readonly LicenciaRepository $repositorioLicencias,
        private readonly EntityManagerInterface $em,
        private readonly string $keypairBase64,
    ) {
        if ($this->keypairBase64 === '') {
            throw new \RuntimeException('LICENSE_KEYPAIR no está configurado. Ejecuta grova:licencias:generar-keypair.');
        }
    }

    /**
     * @param list<string> $modulos
     */
    public function generar(Tenant $tenant, int $duracionDias, array $modulos, ?string $notas = null): Licencia
    {
        $ahora = new \DateTimeImmutable();
        $vencimiento = $ahora->modify("+{$duracionDias} days");

        $payload = [
            'v'           => 1,
            'tenant'      => $tenant->getSlug(),
            'issued_at'   => $ahora->getTimestamp(),
            'expires_at'  => $vencimiento->getTimestamp(),
            'duration_days' => $duracionDias,
            'modules'     => $modulos,
        ];

        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $keypair = base64_decode($this->keypairBase64, true);

        if ($keypair === false || strlen($keypair) !== SODIUM_CRYPTO_SIGN_KEYPAIRBYTES) {
            throw new \RuntimeException('LICENSE_KEYPAIR inválido. Regenera con grova:licencias:generar-keypair.');
        }

        $clavePrivada = sodium_crypto_sign_secretkey($keypair);
        $mensajeFirmado = sodium_crypto_sign($payloadJson, $clavePrivada);
        sodium_memzero($clavePrivada);

        $clave = base64_encode($mensajeFirmado);

        $licencia = new Licencia();
        $licencia->setTenant($tenant);
        $licencia->setClave($clave);
        $licencia->setEstado('activa');
        $licencia->setFechaEmision($ahora);
        $licencia->setFechaVencimiento($vencimiento);
        $licencia->setDuracionDias($duracionDias);
        $licencia->setModulos($modulos);
        $licencia->setNotas($notas);

        $this->em->persist($licencia);
        $this->em->flush();

        return $licencia;
    }

    public function revocar(Licencia $licencia): void
    {
        $licencia->setEstado('revocada');
        $this->em->flush();
    }
}
