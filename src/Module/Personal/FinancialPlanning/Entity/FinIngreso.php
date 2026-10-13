<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinIngresoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinIngresoRepository::class)]
#[ORM\Table(name: 'fin_ingreso')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_ingreso_tenant')]
#[ORM\Index(columns: ['presupuesto_id'], name: 'idx_fin_ingreso_presupuesto')]
class FinIngreso
{
    public const MONEDA_HNL = 'HNL';
    public const MONEDA_USD = 'USD';
    public const MONEDA_EUR = 'EUR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: FinPresupuesto::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FinPresupuesto $presupuesto = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(length: 200)]
    private string $fuente;

    #[ORM\Column(length: 3, options: ['default' => 'HNL'])]
    private string $moneda = self::MONEDA_HNL;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $montoBruto;

    /** Comisión bancaria en moneda original. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $comisiones = '0.00';

    /** Retenciones SAR / ISR en moneda original. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $retenciones = '0.00';

    /** Tipo de cambio al día de la transacción (1.0 si moneda=HNL). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 5, options: ['default' => 1])]
    private string $tasaCambio = '1.00000';

    /** (bruto - comisiones - retenciones) × tasaCambio */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $netoHnl;

    /**
     * Ahorro reservado automáticamente (solo Babilonia, ≥10% del netoHnl).
     * En presupuesto Libre puede ser 0.
     */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $ahorroReservadoHnl = '0.00';

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

    public function getPresupuesto(): ?FinPresupuesto { return $this->presupuesto; }
    public function setPresupuesto(?FinPresupuesto $v): static { $this->presupuesto = $v; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $v): static { $this->fecha = $v; return $this; }

    public function getFuente(): string { return $this->fuente; }
    public function setFuente(string $v): static { $this->fuente = $v; return $this; }

    public function getMoneda(): string { return $this->moneda; }
    public function setMoneda(string $v): static { $this->moneda = $v; return $this; }

    public function getMontoBruto(): float { return (float) $this->montoBruto; }
    public function setMontoBruto(float $v): static { $this->montoBruto = (string) $v; return $this; }

    public function getComisiones(): float { return (float) $this->comisiones; }
    public function setComisiones(float $v): static { $this->comisiones = (string) $v; return $this; }

    public function getRetenciones(): float { return (float) $this->retenciones; }
    public function setRetenciones(float $v): static { $this->retenciones = (string) $v; return $this; }

    public function getTasaCambio(): float { return (float) $this->tasaCambio; }
    public function setTasaCambio(float $v): static { $this->tasaCambio = (string) $v; return $this; }

    public function getNetoHnl(): float { return (float) $this->netoHnl; }
    public function setNetoHnl(float $v): static { $this->netoHnl = (string) $v; return $this; }

    public function getAhorroReservadoHnl(): float { return (float) $this->ahorroReservadoHnl; }
    public function setAhorroReservadoHnl(float $v): static { $this->ahorroReservadoHnl = (string) $v; return $this; }

    public function getNota(): ?string { return $this->nota; }
    public function setNota(?string $v): static { $this->nota = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }

    /** Neto disponible para gastar (excluye ahorro reservado en Babilonia). */
    public function getDisponibleHnl(): float
    {
        return $this->getNetoHnl() - $this->getAhorroReservadoHnl();
    }
}
