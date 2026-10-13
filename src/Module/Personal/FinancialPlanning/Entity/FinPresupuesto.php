<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinPresupuestoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinPresupuestoRepository::class)]
#[ORM\Table(name: 'fin_presupuesto')]
#[ORM\UniqueConstraint(name: 'uq_fin_presupuesto_tenant_mes', columns: ['tenant_id', 'anio', 'mes'])]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_presupuesto_tenant')]
class FinPresupuesto
{
    public const TIPO_LIBRE     = 'libre';
    public const TIPO_BABILONIA = 'babilonia';

    public const PLANTILLA_DABASIR      = 'dabasir_70_10_20';
    public const PLANTILLA_ESTANDAR     = 'estandar_50_30_20';
    public const PLANTILLA_PERSONALIZADA = 'personalizada';

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ACTIVO   = 'activo';
    public const ESTADO_CERRADO  = 'cerrado';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(type: 'smallint')]
    private int $anio;

    #[ORM\Column(type: 'smallint')]
    private int $mes;

    #[ORM\Column(length: 10, options: ['default' => 'libre'])]
    private string $tipo = self::TIPO_LIBRE;

    #[ORM\Column(length: 20, options: ['default' => 'babilonia_dabasir'])]
    private string $plantilla = 'babilonia_dabasir';

    #[ORM\Column(length: 20, options: ['default' => 'borrador'])]
    private string $estado = self::ESTADO_BORRADOR;

    /** Ingreso neto total del mes en HNL (suma de fin_ingreso.neto_hnl). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $ingresoNetoHnl = '0.00';

    /** Snapshot JSON de porcentajes de bolsas al crear el presupuesto. */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $porcentajesSnapshot = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $nota = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoAt;

    public function __construct()
    {
        $this->creadoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getAnio(): int { return $this->anio; }
    public function setAnio(int $v): static { $this->anio = $v; return $this; }

    public function getMes(): int { return $this->mes; }
    public function setMes(int $v): static { $this->mes = $v; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $v): static { $this->tipo = $v; return $this; }

    public function isBabilonia(): bool { return $this->tipo === self::TIPO_BABILONIA; }

    public function getPlantilla(): string { return $this->plantilla; }
    public function setPlantilla(string $v): static { $this->plantilla = $v; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $v): static { $this->estado = $v; return $this; }

    public function getIngresoNetoHnl(): float { return (float) $this->ingresoNetoHnl; }
    public function setIngresoNetoHnl(float $v): static { $this->ingresoNetoHnl = (string) $v; return $this; }

    public function getPorcentajesSnapshot(): ?array { return $this->porcentajesSnapshot; }
    public function setPorcentajesSnapshot(?array $v): static { $this->porcentajesSnapshot = $v; return $this; }

    public function getNota(): ?string { return $this->nota; }
    public function setNota(?string $v): static { $this->nota = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }

    public function getMesLabel(): string
    {
        return (new \DateTimeImmutable("{$this->anio}-{$this->mes}-01"))->format('F Y');
    }
}
