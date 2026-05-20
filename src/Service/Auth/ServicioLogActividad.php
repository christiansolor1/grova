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

    /**
     * @return string|null El sessionToken generado (solo para login/2fa exitoso), o null
     */
    public function registrar(
        string $accion,
        ?User $usuario = null,
        ?string $email = null,
        ?array $detalles = null,
    ): ?string {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        $registro = LogActividad::crear(
            accion: $accion,
            ip: $request->getClientIp() ?? '0.0.0.0',
            usuario: $usuario,
            email: $email,
            userAgent: $request->headers->get('User-Agent'),
            detalles: $detalles,
        );

        // Generar sessionToken para inicios de sesión exitosos (permite revocación individual)
        if (\in_array($accion, [LogActividad::ACCION_LOGIN_EXITOSO, LogActividad::ACCION_2FA_EXITOSO], true)) {
            $token = bin2hex(random_bytes(32));
            $registro->setSessionToken($token);
        }

        $this->em->persist($registro);
        $this->em->flush();

        return $registro->getSessionToken();
    }
}
