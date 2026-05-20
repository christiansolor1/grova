<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\User;
use App\Mail\CorreoRecuperacionContrasena;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ServicioRecuperacionContrasena
{
    public function __construct(
        private readonly EntityManagerInterface $emCore,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Genera un token y envía el email. Silencioso si el email no existe
     * (no revelar si una cuenta existe o no).
     */
    public function solicitarReset(string $email): void
    {
        $usuario = $this->emCore->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$usuario instanceof User) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        $usuario->setResetToken($token);
        $usuario->setResetTokenExpira(new \DateTimeImmutable('+1 hour'));
        $this->emCore->flush();

        $enlace = $this->urlGenerator->generate(
            'nueva_contrasena',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        try {
            $this->mailer->send(new CorreoRecuperacionContrasena($usuario, $enlace));
        } catch (\Throwable $e) {
            $this->logger->error('No se pudo enviar el correo de recuperación.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function validarToken(string $token): User
    {
        $usuario = $this->emCore->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$usuario instanceof User) {
            throw new \DomainException('El enlace no es válido.');
        }

        $expira = $usuario->getResetTokenExpira();

        if (!$expira || $expira < new \DateTimeImmutable()) {
            throw new \DomainException('El enlace expiró. Solicita uno nuevo.');
        }

        return $usuario;
    }

    public function cambiarContrasena(string $token, string $nuevaContrasena): User
    {
        $usuario = $this->validarToken($token);

        $usuario->setPassword($this->hasher->hashPassword($usuario, $nuevaContrasena));
        $usuario->setResetToken(null);
        $usuario->setResetTokenExpira(null);
        $this->emCore->flush();

        return $usuario;
    }
}
