<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\LogActividad;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ServicioLogActividad
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function registrar(
        string $accion,
        ?User $usuario = null,
        ?string $email = null,
        ?array $detalles = null,
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        $registro = LogActividad::crear(
            accion: $accion,
            ip: $request->getClientIp() ?? '0.0.0.0',
            usuario: $usuario,
            email: $email,
            userAgent: $request->headers->get('User-Agent'),
            detalles: $detalles,
        );

        $this->em->persist($registro);
        $this->em->flush();
    }
}
