<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinGastoRepository;
use Doctrine\ORM\Mapping as ORM;

/** Gasto variable del mes. */
#[ORM\Entity(repositoryClass: FinGastoRepository::class)]
#[ORM\Table(name: 'fin_gasto')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_gasto_tenant')]
#[ORM\Index(columns: ['fecha'], name: 'idx_fin_gasto_fecha')]
class FinGasto
{
    public const NATURALEZA_NECESIDAD = 'necesidad';
    public const NATURALEZA_DESEO     = 'deseo';
    public const NATURALEZA_AHORRO    = 'ahorro';
    public const NATURALEZA_DEUDA     = 'deuda';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: FinBolsa::class)]
    #[ORM\JoinColumn(nullable: false)]
    private FinBolsa $bolsa;

    /** Presupuesto mensual al que aplica este gasto. */
    #[ORM\ManyToOne(targetEntity: FinPresupuesto::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FinPresupuesto $presupuesto = null;

    /** Evento o viaje opcional al que pertenece este gasto. */
    #[ORM\ManyToOne(targetEntity: FinEvento::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FinEvento $evento = null;

    #[ORM\Column(length: 200)]
    private string $descripcion;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $monto;

    #[ORM\Column(length: 3, options: ['default' => 'HNL'])]
    private string $moneda = 'HNL';

    #[ORM\Column(type: 'decimal', precision: 14, scale: 5, options: ['default' => 1])]
    private string $tasaCambio = '1.00000';

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $montoHnl;

    /** necesidad | deseo | ahorro | deuda */
    #[ORM\Column(length: 20, options: ['default' => 'necesidad'])]
    private string $naturaleza = self::NATURALEZA_NECESIDAD;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $metodo = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $comprobantePath = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $nota = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoAt;

    public function __construct()
    {
        $this->fecha    = new \DateTimeImmutable();
        $this->creadoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getBolsa(): FinBolsa { return $this->bolsa; }
    public function setBolsa(FinBolsa $v): static { $this->bolsa = $v; return $this; }

    public function getPresupuesto(): ?FinPresupuesto { return $this->presupuesto; }
    public function setPresupuesto(?FinPresupuesto $v): static { $this->presupuesto = $v; return $this; }

    public function getEvento(): ?FinEvento { return $this->evento; }
    public function setEvento(?FinEvento $v): static { $this->evento = $v; return $this; }

    public function getDescripcion(): string { return $this->descripcion; }
    public function setDescripcion(string $v): static { $this->descripcion = $v; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $v): static { $this->fecha = $v; return $this; }

    public function getMonto(): float { return (float) $this->monto; }
    public function setMonto(float $v): static { $this->monto = (string) $v; return $this; }

    public function getMoneda(): string { return $this->moneda; }
    public function setMoneda(string $v): static { $this->moneda = $v; return $this; }

    public function getTasaCambio(): float { return (float) $this->tasaCambio; }
    public function setTasaCambio(float $v): static { $this->tasaCambio = (string) $v; return $this; }

    public function getMontoHnl(): float { return (float) $this->montoHnl; }
    public function setMontoHnl(float $v): static { $this->montoHnl = (string) $v; return $this; }

    public function getNaturaleza(): string { return $this->naturaleza; }
    public function setNaturaleza(string $v): static { $this->naturaleza = $v; return $this; }

    public function getMetodo(): ?string { return $this->metodo; }
    public function setMetodo(?string $v): static { $this->metodo = $v; return $this; }

    public function getComprobantePath(): ?string { return $this->comprobantePath; }
    public function setComprobantePath(?string $v): static { $this->comprobantePath = $v; return $this; }

    public function getNota(): ?string { return $this->nota; }
    public function setNota(?string $v): static { $this->nota = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }

    public function esNecesidad(): bool { return $this->naturaleza === self::NATURALEZA_NECESIDAD; }
    public function esDeseo(): bool { return $this->naturaleza === self::NATURALEZA_DESEO; }
}
