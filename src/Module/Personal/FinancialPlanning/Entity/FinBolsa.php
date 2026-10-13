<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinBolsaRepository;
use Doctrine\ORM\Mapping as ORM;

/** Bolsa de presupuesto (global por tenant, no por mes). */
#[ORM\Entity(repositoryClass: FinBolsaRepository::class)]
#[ORM\Table(name: 'fin_bolsa')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_bolsa_tenant')]
class FinBolsa
{
    public const TIPO_NECESIDAD  = 'necesidad';
    public const TIPO_GUSTO      = 'gusto';
    public const TIPO_AHORRO     = 'ahorro';
    public const TIPO_DEUDA      = 'deuda';
    public const TIPO_INVERSION  = 'inversion';
    public const TIPO_FAMILIA    = 'familia';

    public const TIPOS = [
        self::TIPO_NECESIDAD,
        self::TIPO_GUSTO,
        self::TIPO_AHORRO,
        self::TIPO_DEUDA,
        self::TIPO_INVERSION,
        self::TIPO_FAMILIA,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 100)]
    private string $nombre;

    #[ORM\Column(length: 20)]
    private string $tipo;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 0])]
    private string $porcentajeSugerido = '0.00';

    #[ORM\Column]
    private int $orden = 0;

    /** Color hex para UI (ej: #3b82f6). */
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $activa = true;

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $v): static { $this->nombre = $v; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $v): static { $this->tipo = $v; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $v): static { $this->descripcion = $v; return $this; }

    public function getPorcentajeSugerido(): float { return (float) $this->porcentajeSugerido; }
    public function setPorcentajeSugerido(float $v): static { $this->porcentajeSugerido = (string) $v; return $this; }

    public function getOrden(): int { return $this->orden; }
    public function setOrden(int $v): static { $this->orden = $v; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $v): static { $this->color = $v; return $this; }

    public function isActiva(): bool { return $this->activa; }
    public function setActiva(bool $v): static { $this->activa = $v; return $this; }
}
