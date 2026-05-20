<?php

declare(strict_types=1);

namespace App\DTO\Admin;

/**
 * Fila del listado de empresas en el panel Super Admin.
 */
final readonly class FilaEmpresaAdmin
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $slug,
        public string $estado,
        public ?string $tipo,
        public int $totalUsuarios,
        public ?string $nombrePlan,
        public ?string $estadoSuscripcion,
        public ?\DateTimeImmutable $fechaVencimiento,
    ) {
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'activo';
    }

    public function etiquetaEstado(): string
    {
        return $this->estaActiva() ? 'Activa' : 'Inactiva';
    }

    public function suscripcionVencida(): bool
    {
        return $this->estadoSuscripcion === 'vencida'
            || ($this->fechaVencimiento !== null && $this->fechaVencimiento < new \DateTimeImmutable('today'));
    }

    public function esCortesia(): bool
    {
        return $this->tipo === 'cortesia';
    }

    public function esStaff(): bool
    {
        return $this->tipo === 'staff';
    }

    public function esTrial(): bool
    {
        return $this->tipo === 'trial';
    }

    public function esPago(): bool
    {
        return $this->tipo === 'pago';
    }

    public function etiquetaTipo(): string
    {
        return match ($this->tipo) {
            'staff'    => 'Staff',
            'trial'    => 'Prueba',
            'cortesia' => 'Cortesía',
            'pago'     => 'Pago',
            default    => '—',
        };
    }
}
