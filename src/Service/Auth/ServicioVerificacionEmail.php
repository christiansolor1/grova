<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\User;
use App\Mail\CorreoVerificacionEmail;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ServicioVerificacionEmail
{
    public function __construct(
        private readonly EntityManagerInterface $emCore,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function enviarVerificacion(User $usuario): void
    {
        $token = bin2hex(random_bytes(32));

        $usuario->setTokenVerificacion($token);
        $usuario->setTokenVerificaExpira(new \DateTimeImmutable('+24 hours'));
        $this->emCore->flush();

        $enlace = $this->urlGenerator->generate(
            'verificar_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        try {
            $this->mailer->send(new CorreoVerificacionEmail($usuario, $enlace));
        } catch (\Throwable $e) {
            $this->logger->error('No se pudo enviar el correo de verificación.', [
                'email' => $usuario->getEmail(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function verificar(string $token): User
    {
        $usuario = $this->emCore->getRepository(User::class)->findOneBy([
            'tokenVerificacion' => $token,
        ]);

        if (!$usuario instanceof User) {
            throw new \DomainException('El enlace de verificación no es válido.');
        }

        $expira = $usuario->getTokenVerificaExpira();

        if (!$expira || $expira < new \DateTimeImmutable()) {
            throw new \DomainException('El enlace expiró. Solicita uno nuevo desde el login.');
        }

        $usuario->setEmailVerificado(true);
        $usuario->setTokenVerificacion(null);
        $usuario->setTokenVerificaExpira(null);
        $this->emCore->flush();

        return $usuario;
    }

    public function reenviar(string $email): void
    {
        $usuario = $this->emCore->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$usuario instanceof User || $usuario->isEmailVerificado()) {
            return; // silencioso — no revelar si el email existe
        }

        $this->enviarVerificacion($usuario);
    }
}
