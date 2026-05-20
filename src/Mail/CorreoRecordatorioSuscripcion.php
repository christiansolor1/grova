<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class CorreoRecordatorioSuscripcion extends TemplatedEmail
{
    /**
     * @param array<string, mixed> $context Datos adicionales (tenant, fecha_vencimiento, dias_restantes, etc.)
     */
    public function __construct(User $usuario, string $tenantNombre, string $fechaVencimiento, int $diasRestantes)
    {
        parent::__construct();

        $this
            ->to(new Address((string) $usuario->getEmail(), $usuario->getNombreCompleto()))
            ->subject('Tu suscripción está por vencer — Grova')
            ->htmlTemplate('emails/recordatorio_suscripcion.html.twig')
            ->context([
                'usuario'          => $usuario,
                'tenant_nombre'    => $tenantNombre,
                'fecha_vencimiento' => $fechaVencimiento,
                'dias_restantes'   => $diasRestantes,
            ]);
    }
}
