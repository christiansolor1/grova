<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinCompromisoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinCompromisoRepository::class)]
#[ORM\Table(name: 'fin_compromiso')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_compromiso_tenant')]
class FinCompromiso
{
    public const TIPO_SERVICIO      = 'servicio';
    public const TIPO_SUSCRIPCION   = 'suscripcion';
    public const TIPO_PRESTAMO      = 'prestamo';
    public const TIPO_CREDITO       = 'credito';
    public const TIPO_DEUDA_PERSONA = 'deuda_persona';
    public const TIPO_AHORRO        = 'ahorro';

    public const FRECUENCIA_MENSUAL    = 'mensual';
    public const FRECUENCIA_QUINCENAL  = 'quincenal';
    public const FRECUENCIA_ANUAL      = 'anual';
    public const FRECUENCIA_UNICA      = 'unica';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\ManyToOne(targetEntity: FinBolsa::class)]
    #[ORM\JoinColumn(nullable: false)]
    private FinBolsa $bolsa;

    #[ORM\Column(length: 20)]
    private string $tipo;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $montoCuota;

    #[ORM\Column(length: 3, options: ['default' => 'HNL'])]
    private string $moneda = 'HNL';

    #[ORM\Column(length: 15, options: ['default' => 'mensual'])]
    private string $frecuencia = self::FRECUENCIA_MENSUAL;

    /** Día del mes en que vence (1–31). */
    #[ORM\Column(type: 'smallint')]
    private int $diaPago = 1;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaInicio;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaFin = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $contacto = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $activo = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoAt;

    public function __construct()
    {
        $this->fechaInicio = new \DateTimeImmutable('first day of this month');
        $this->creadoAt    = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $v): static { $this->nombre = $v; return $this; }

    public function getBolsa(): FinBolsa { return $this->bolsa; }
    public function setBolsa(FinBolsa $v): static { $this->bolsa = $v; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $v): static { $this->tipo = $v; return $this; }

    public function getMontoCuota(): float { return (float) $this->montoCuota; }
    public function setMontoCuota(float $v): static { $this->montoCuota = (string) $v; return $this; }

    public function getMoneda(): string { return $this->moneda; }
    public function setMoneda(string $v): static { $this->moneda = $v; return $this; }

    public function getFrecuencia(): string { return $this->frecuencia; }
    public function setFrecuencia(string $v): static { $this->frecuencia = $v; return $this; }

    public function getDiaPago(): int { return $this->diaPago; }
    public function setDiaPago(int $v): static { $this->diaPago = $v; return $this; }

    public function getFechaInicio(): \DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(\DateTimeImmutable $v): static { $this->fechaInicio = $v; return $this; }

    public function getFechaFin(): ?\DateTimeImmutable { return $this->fechaFin; }
    public function setFechaFin(?\DateTimeImmutable $v): static { $this->fechaFin = $v; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $v): static { $this->descripcion = $v; return $this; }

    public function getContacto(): ?string { return $this->contacto; }
    public function setContacto(?string $v): static { $this->contacto = $v; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $v): static { $this->activo = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }

    public function esPrestamo(): bool { return $this->tipo === self::TIPO_PRESTAMO; }

    /**
     * Calcula el total del plan completo (todas las cuotas proyectadas × monto).
     * Para frecuencia unica devuelve el monto de una sola cuota.
     */
    public function getTotalPlan(): float
    {
        $monto = $this->getMontoCuota();

        if ($this->frecuencia === self::FRECUENCIA_UNICA) {
            return $monto;
        }

        $fin = $this->fechaFin;
        if ($fin === null) {
            return 0.0; // abierto, no se puede calcular
        }

        $inicio = new \DateTimeImmutable(sprintf('%04d-%02d-01', (int)$this->fechaInicio->format('Y'), (int)$this->fechaInicio->format('n')));
        $finMes = new \DateTimeImmutable(sprintf('%04d-%02d-01', (int)$fin->format('Y'), (int)$fin->format('n')));

        $meses = (((int)$finMes->format('Y')) - ((int)$inicio->format('Y'))) * 12
               + ((int)$finMes->format('n')) - ((int)$inicio->format('n'))
               + 1;

        if ($this->frecuencia === self::FRECUENCIA_QUINCENAL) {
            return $meses * 2 * $monto;
        }
        if ($this->frecuencia === self::FRECUENCIA_ANUAL) {
            return ceil($meses / 12) * $monto;
        }

        return $meses * $monto; // mensual
    }

    /** Devuelve true si el compromiso aplica en el mes/año dados. */
    public function aplicaEnMes(int $anio, int $mes): bool
    {
        $primerDiaMes  = new \DateTimeImmutable("{$anio}-{$mes}-01");
        $ultimoDiaMes  = $primerDiaMes->modify('last day of this month');

        if ($this->fechaInicio > $ultimoDiaMes) {
            return false;
        }
        if ($this->fechaFin !== null && $this->fechaFin < $primerDiaMes) {
            return false;
        }

        return true;
    }
}
