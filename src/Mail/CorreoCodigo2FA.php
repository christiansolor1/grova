<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class CorreoCodigo2FA extends TemplatedEmail
{
    public function __construct(User $usuario, string $codigo)
    {
        parent::__construct();

        $nombre = $usuario->getNombre() ? ' ' . $usuario->getNombre() : '';
        $textoPlano = <<<TEXT
Tu código de verificación es: {$codigo}

Hola{$nombre}, usa el siguiente código para iniciar sesión. Expira en 5 minutos.

Si no intentaste iniciar sesión, ignora este correo. Tu cuenta está segura.
TEXT;

        $this
            ->to(new Address((string) $usuario->getEmail(), $usuario->getNombreCompleto()))
            ->subject('Tu código de verificación — Grova')
            ->text($textoPlano)
            ->htmlTemplate('emails/codigo_2fa.html.twig')
            ->context([
                'usuario' => $usuario,
                'codigo'  => $codigo,
            ]);
    }
}
