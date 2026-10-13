<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinMetaRepository;
use Doctrine\ORM\Mapping as ORM;

/** Meta de ahorro o inversión (fondo de emergencia, vivienda, retiro, compra, etc.). */
#[ORM\Entity(repositoryClass: FinMetaRepository::class)]
#[ORM\Table(name: 'fin_meta')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_meta_tenant')]
class FinMeta
{
    public const TIPO_EMERGENCIA = 'emergencia';
    public const TIPO_VIVIENDA   = 'vivienda';
    public const TIPO_RETIRO     = 'retiro';
    public const TIPO_INVERSION  = 'inversion';
    public const TIPO_COMPRA     = 'compra';
    public const TIPO_VIAJE      = 'viaje';
    public const TIPO_EDUCACION  = 'educacion';
    public const TIPO_OTRO       = 'otro';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\Column(length: 20, options: ['default' => 'otro'])]
    private string $tipo = self::TIPO_OTRO;

    /** Monto objetivo en HNL. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $montoObjetivo;

    /** Acumulado actual en HNL (suma de abonos). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $montoAcumulado = '0.00';

    /** Aporte mensual sugerido calculado al crear/actualizar la meta. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $aporteMensualSugerido = '0.00';

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaObjetivo = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $activa = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoAt;

    public function __construct()
    {
        $this->creadoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $v): static { $this->nombre = $v; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $v): static { $this->tipo = $v; return $this; }

    public function getMontoObjetivo(): float { return (float) $this->montoObjetivo; }
    public function setMontoObjetivo(float $v): static { $this->montoObjetivo = (string) $v; return $this; }

    public function getMontoAcumulado(): float { return (float) $this->montoAcumulado; }
    public function setMontoAcumulado(float $v): static { $this->montoAcumulado = (string) $v; return $this; }

    public function getAporteMensualSugerido(): float { return (float) $this->aporteMensualSugerido; }
    public function setAporteMensualSugerido(float $v): static { $this->aporteMensualSugerido = (string) $v; return $this; }

    public function getFechaObjetivo(): ?\DateTimeImmutable { return $this->fechaObjetivo; }
    public function setFechaObjetivo(?\DateTimeImmutable $v): static { $this->fechaObjetivo = $v; return $this; }

    public function isActiva(): bool { return $this->activa; }
    public function setActiva(bool $v): static { $this->activa = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }

    public function getPorcentajeAvance(): float
    {
        if ($this->getMontoObjetivo() <= 0) return 0.0;
        return min(100.0, round(($this->getMontoAcumulado() / $this->getMontoObjetivo()) * 100, 1));
    }

    public function getFaltante(): float
    {
        return max(0.0, $this->getMontoObjetivo() - $this->getMontoAcumulado());
    }

    /**
     * Calcula el aporte mensual necesario dado un número de meses restantes.
     * No persiste — usar setAporteMensualSugerido() para guardar.
     */
    public function calcularAporteMensual(int $mesesRestantes): float
    {
        if ($mesesRestantes <= 0) return $this->getFaltante();
        return round($this->getFaltante() / $mesesRestantes, 2);
    }
}
