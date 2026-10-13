<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinInversionRepository;
use Doctrine\ORM\Mapping as ORM;

/** Instrumento de inversión: acciones, fondos, cripto, depósito plazo, etc. */
#[ORM\Entity(repositoryClass: FinInversionRepository::class)]
#[ORM\Table(name: 'fin_inversion')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_fin_inversion_tenant')]
class FinInversion
{
    public const TIPO_ACCIONES      = 'acciones';
    public const TIPO_FONDO         = 'fondo';
    public const TIPO_CRIPTO        = 'cripto';
    public const TIPO_DEPOSITO      = 'deposito_plazo';
    public const TIPO_BIEN_RAIZ     = 'bien_raiz';
    public const TIPO_NEGOCIO       = 'negocio';
    public const TIPO_OTRO          = 'otro';

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

    /** Institución o plataforma (Banco Atlántida, Binance, etc.). */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $institucion = null;

    /** Monto total invertido en HNL (suma de aportes). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $montoInvertidoHnl = '0.00';

    /** Valor actual estimado en HNL. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $valorActualHnl = '0.00';

    /** Rendimiento anual esperado (0.08 = 8%). */
    #[ORM\Column(type: 'decimal', precision: 7, scale: 4, options: ['default' => 0])]
    private string $rendimientoEsperado = '0.0000';

    /** Rendimiento anual real calculado al cierre del año. */
    #[ORM\Column(type: 'decimal', precision: 7, scale: 4, nullable: true)]
    private ?string $rendimientoReal = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaInicio = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $activa = true;

    #[ORM\Column(length: 300, nullable: true)]
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

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $v): static { $this->nombre = $v; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $v): static { $this->tipo = $v; return $this; }

    public function getInstitucion(): ?string { return $this->institucion; }
    public function setInstitucion(?string $v): static { $this->institucion = $v; return $this; }

    public function getMontoInvertidoHnl(): float { return (float) $this->montoInvertidoHnl; }
    public function setMontoInvertidoHnl(float $v): static { $this->montoInvertidoHnl = (string) $v; return $this; }

    public function getValorActualHnl(): float { return (float) $this->valorActualHnl; }
    public function setValorActualHnl(float $v): static { $this->valorActualHnl = (string) $v; return $this; }

    public function getRendimientoEsperado(): float { return (float) $this->rendimientoEsperado; }
    public function setRendimientoEsperado(float $v): static { $this->rendimientoEsperado = (string) $v; return $this; }

    public function getRendimientoReal(): ?float { return $this->rendimientoReal !== null ? (float) $this->rendimientoReal : null; }
    public function setRendimientoReal(?float $v): static { $this->rendimientoReal = $v !== null ? (string) $v : null; return $this; }

    public function getFechaInicio(): ?\DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(?\DateTimeImmutable $v): static { $this->fechaInicio = $v; return $this; }

    public function isActiva(): bool { return $this->activa; }
    public function setActiva(bool $v): static { $this->activa = $v; return $this; }

    public function getNota(): ?string { return $this->nota; }
    public function setNota(?string $v): static { $this->nota = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }

    /** Ganancia o pérdida actual en HNL. */
    public function getGananciaHnl(): float
    {
        return round($this->getValorActualHnl() - $this->getMontoInvertidoHnl(), 2);
    }

    public function getPorcentajeRetorno(): float
    {
        if ($this->getMontoInvertidoHnl() <= 0) return 0.0;
        return round(($this->getGananciaHnl() / $this->getMontoInvertidoHnl()) * 100, 2);
    }
}
