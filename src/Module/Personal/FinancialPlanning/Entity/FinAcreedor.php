<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinAcreedorRepository;
use Doctrine\ORM\Mapping as ORM;

/** Acreedor para plan Dabasir (reparto proporcional) y estrategias Avalancha/Bola de nieve. */
#[ORM\Entity(repositoryClass: FinAcreedorRepository::class)]
#[ORM\Table(name: 'fin_acreedor')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_acreedor_tenant')]
class FinAcreedor
{
    public const TIPO_PRESTAMO = 'prestamo';
    public const TIPO_CREDITO  = 'credito';
    public const TIPO_PERSONA  = 'persona';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $saldoHnl;

    /** Tasa nominal anual (0 para deudas sin interés). */
    #[ORM\Column(type: 'decimal', precision: 7, scale: 4, options: ['default' => 0])]
    private string $tasaAnual = '0.0000';

    #[ORM\Column(length: 20)]
    private string $tipo;

    /** FK opcional al compromiso que genera las cuotas. */
    #[ORM\ManyToOne(targetEntity: FinCompromiso::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FinCompromiso $compromiso = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $activo = true;

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $v): static { $this->nombre = $v; return $this; }

    public function getSaldoHnl(): float { return (float) $this->saldoHnl; }
    public function setSaldoHnl(float $v): static { $this->saldoHnl = (string) $v; return $this; }

    public function getTasaAnual(): float { return (float) $this->tasaAnual; }
    public function setTasaAnual(float $v): static { $this->tasaAnual = (string) $v; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $v): static { $this->tipo = $v; return $this; }

    public function getCompromiso(): ?FinCompromiso { return $this->compromiso; }
    public function setCompromiso(?FinCompromiso $v): static { $this->compromiso = $v; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $v): static { $this->activo = $v; return $this; }

    /**
     * Calcula cuánto le corresponde en el plan Dabasir dado el total disponible para deudas.
     * Reparto proporcional al saldo.
     */
    public function montoProporional(float $totalDeudas, float $disponibleParaDeudas): float
    {
        if ($totalDeudas <= 0) return 0.0;
        return round(($this->getSaldoHnl() / $totalDeudas) * $disponibleParaDeudas, 2);
    }
}
