<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class CorreoAlertaSeguridad extends TemplatedEmail
{
    /**
     * @param array<string, mixed> $detalles Datos adicionales (ip, userAgent, intentos, etc.)
     */
    public function __construct(User $usuario, string $metodo, ?array $detalles = null)
    {
        parent::__construct();

        $this
            ->to(new Address((string) $usuario->getEmail(), $usuario->getNombreCompleto()))
            ->subject('Alerta de seguridad: intentos fallidos en tu cuenta — Grova')
            ->htmlTemplate('emails/alerta_seguridad.html.twig')
            ->context([
                'usuario'  => $usuario,
                'metodo'   => $metodo,
                'detalles' => $detalles ?? [],
                'fecha'    => new \DateTimeImmutable(),
            ]);
    }
}
