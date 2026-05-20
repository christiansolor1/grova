<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class EstadoSubscripcion
{
    public const ACTIVA = 'activa';
    public const VENCIDA = 'vencida';
    public const SIN_SUSCRIPCION = 'sin_suscripcion';
    public const SUPER_ADMIN = 'super_admin';

    private function __construct(
        public string $estado,
    ) {
    }

    public static function activa(): self
    {
        return new self(self::ACTIVA);
    }

    public static function vencida(): self
    {
        return new self(self::VENCIDA);
    }

    public static function sinSuscripcion(): self
    {
        return new self(self::SIN_SUSCRIPCION);
    }

    public static function superAdmin(): self
    {
        return new self(self::SUPER_ADMIN);
    }

    public function permitirAcceso(): bool
    {
        return $this->estado === self::ACTIVA || $this->estado === self::SUPER_ADMIN;
    }

    public function esBloqueante(): bool
    {
        return !$this->permitirAcceso();
    }
}
