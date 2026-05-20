<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class CorreoRecuperacionContrasena extends TemplatedEmail
{
    public function __construct(User $usuario, string $enlace)
    {
        parent::__construct();

        $this
            ->to(new Address((string) $usuario->getEmail(), $usuario->getNombreCompleto()))
            ->subject('Recupera tu contraseña — Grova')
            ->htmlTemplate('emails/recuperacion_contrasena.html.twig')
            ->context([
                'usuario' => $usuario,
                'enlace'  => $enlace,
            ]);
    }
}
