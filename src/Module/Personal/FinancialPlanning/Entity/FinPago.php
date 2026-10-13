<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinPagoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinPagoRepository::class)]
#[ORM\Table(name: 'fin_pago')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_pago_tenant')]
#[ORM\Index(columns: ['cuota_id'], name: 'idx_fin_pago_cuota')]
class FinPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    /** Cuota a la que aplica este pago (nullable: pagos sin cuota formal). */
    #[ORM\ManyToOne(targetEntity: FinCuota::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FinCuota $cuota = null;

    #[ORM\ManyToOne(targetEntity: FinCompromiso::class)]
    #[ORM\JoinColumn(nullable: false)]
    private FinCompromiso $compromiso;

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

    public function getCuota(): ?FinCuota { return $this->cuota; }
    public function setCuota(?FinCuota $v): static { $this->cuota = $v; return $this; }

    public function getCompromiso(): FinCompromiso { return $this->compromiso; }
    public function setCompromiso(FinCompromiso $v): static { $this->compromiso = $v; return $this; }

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

    public function getMetodo(): ?string { return $this->metodo; }
    public function setMetodo(?string $v): static { $this->metodo = $v; return $this; }

    public function getComprobantePath(): ?string { return $this->comprobantePath; }
    public function setComprobantePath(?string $v): static { $this->comprobantePath = $v; return $this; }

    public function getNota(): ?string { return $this->nota; }
    public function setNota(?string $v): static { $this->nota = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }
}
