<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\User;
use App\Entity\UserCodigoEmail2FA;
use App\Mail\CorreoCodigo2FA;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

final class ServicioCodigoEmail2FA
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function enviarCodigo(User $usuario): string
    {
        // Invalidar códigos anteriores no usados del mismo usuario
        $repo = $this->em->getRepository(UserCodigoEmail2FA::class);
        $repo->createQueryBuilder('c')
            ->update()
            ->set('c.usado', ':usado')
            ->where('c.usuario = :usuario')
            ->andWhere('c.usado = :falso')
            ->setParameter('usado', true)
            ->setParameter('usuario', $usuario)
            ->setParameter('falso', false)
            ->getQuery()
            ->execute();

        // Generar nuevo código
        $codigo = UserCodigoEmail2FA::generar($usuario);
        $this->em->persist($codigo);
        $this->em->flush();

        // Enviar email
        $this->mailer->send(new CorreoCodigo2FA($usuario, $codigo->getCodigo()));

        return $codigo->getCodigo();
    }

    public function validarCodigo(User $usuario, string $codigo): bool
    {
        $repo = $this->em->getRepository(UserCodigoEmail2FA::class);

        $entity = $repo->findOneBy([
            'usuario' => $usuario,
            'codigo'  => $codigo,
            'usado'   => false,
        ]);

        if (!$entity instanceof UserCodigoEmail2FA) {
            return false;
        }

        if (!$entity->esValido($codigo)) {
            return false;
        }

        $entity->marcarUsado();
        $this->em->flush();

        return true;
    }
}
