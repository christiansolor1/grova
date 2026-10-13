<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinEventoRepository;
use Doctrine\ORM\Mapping as ORM;

/** Evento o viaje al que se pueden asociar gastos (asado, viaje, cumpleaños, etc.). */
#[ORM\Entity(repositoryClass: FinEventoRepository::class)]
#[ORM\Table(name: 'fin_evento')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_evento_tenant')]
class FinEvento
{
    public const TIPO_VIAJE      = 'viaje';
    public const TIPO_CELEBRACION = 'celebracion';
    public const TIPO_PROYECTO   = 'proyecto';
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

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaInicio = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaFin = null;

    /** Presupuesto estimado total para el evento. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $presupuestoEstimado = '0.00';

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $nota = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $cerrado = false;

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

    public function getFechaInicio(): ?\DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(?\DateTimeImmutable $v): static { $this->fechaInicio = $v; return $this; }

    public function getFechaFin(): ?\DateTimeImmutable { return $this->fechaFin; }
    public function setFechaFin(?\DateTimeImmutable $v): static { $this->fechaFin = $v; return $this; }

    public function getPresupuestoEstimado(): float { return (float) $this->presupuestoEstimado; }
    public function setPresupuestoEstimado(float $v): static { $this->presupuestoEstimado = (string) $v; return $this; }

    public function getNota(): ?string { return $this->nota; }
    public function setNota(?string $v): static { $this->nota = $v; return $this; }

    public function isCerrado(): bool { return $this->cerrado; }
    public function setCerrado(bool $v): static { $this->cerrado = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }
}
