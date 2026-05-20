<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class CorreoCambioContrasena extends TemplatedEmail
{
    /**
     * @param array<string, mixed> $detalles Datos del request (ip, userAgent, etc.)
     */
    public function __construct(User $usuario, ?array $detalles = null)
    {
        parent::__construct();

        $this
            ->to(new Address((string) $usuario->getEmail(), $usuario->getNombreCompleto()))
            ->subject('Tu contraseña ha sido cambiada — Grova')
            ->htmlTemplate('emails/cambio_contrasena.html.twig')
            ->context([
                'usuario'  => $usuario,
                'detalles' => $detalles ?? [],
                'fecha'    => new \DateTimeImmutable(),
            ]);
    }
}
