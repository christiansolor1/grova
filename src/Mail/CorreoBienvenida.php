<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\Suscripcion;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class CorreoBienvenida extends TemplatedEmail
{
    public function __construct(User $usuario, Suscripcion $suscripcion)
    {
        parent::__construct();

        $this
            ->to(new Address((string) $usuario->getEmail(), $usuario->getNombreCompleto()))
            ->subject('Bienvenido a Grova — tu prueba de 30 días está activa')
            ->htmlTemplate('emails/bienvenida.html.twig')
            ->context([
                'usuario'     => $usuario,
                'suscripcion' => $suscripcion,
            ]);
    }
}
