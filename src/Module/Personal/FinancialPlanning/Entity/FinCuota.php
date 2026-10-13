<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinCuotaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinCuotaRepository::class)]
#[ORM\Table(name: 'fin_cuota')]
#[ORM\Index(columns: ['compromiso_id'], name: 'idx_fin_cuota_compromiso')]
#[ORM\Index(columns: ['fecha_vencimiento'], name: 'idx_fin_cuota_vencimiento')]
class FinCuota
{
    /** Estados calculados — no se persisten, se evalúan en tiempo real. */
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PARCIAL   = 'parcial';
    public const ESTADO_PAGADO    = 'pagado';
    public const ESTADO_VENCIDO   = 'vencido';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FinCompromiso::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FinCompromiso $compromiso;

    #[ORM\Column(type: 'smallint')]
    private int $numero;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaVencimiento;

    /** Capital de esta cuota (sistema francés; 0 para servicios/suscripciones). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $capital = '0.00';

    /** Interés de esta cuota. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $interes = '0.00';

    /** Otros cargos (seguro, comisiones). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $otros = '0.00';

    /** Monto total a pagar = capital + interés + otros. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $montoTotal;

    /** Suma de pagos recibidos contra esta cuota. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $montoPagado = '0.00';

    public function getId(): ?int { return $this->id; }

    public function getCompromiso(): FinCompromiso { return $this->compromiso; }
    public function setCompromiso(FinCompromiso $v): static { $this->compromiso = $v; return $this; }

    public function getNumero(): int { return $this->numero; }
    public function setNumero(int $v): static { $this->numero = $v; return $this; }

    public function getFechaVencimiento(): \DateTimeImmutable { return $this->fechaVencimiento; }
    public function setFechaVencimiento(\DateTimeImmutable $v): static { $this->fechaVencimiento = $v; return $this; }

    public function getCapital(): float { return (float) $this->capital; }
    public function setCapital(float $v): static { $this->capital = (string) $v; return $this; }

    public function getInteres(): float { return (float) $this->interes; }
    public function setInteres(float $v): static { $this->interes = (string) $v; return $this; }

    public function getOtros(): float { return (float) $this->otros; }
    public function setOtros(float $v): static { $this->otros = (string) $v; return $this; }

    public function getMontoTotal(): float { return (float) $this->montoTotal; }
    public function setMontoTotal(float $v): static { $this->montoTotal = (string) $v; return $this; }

    public function getMontoPagado(): float { return (float) $this->montoPagado; }
    public function setMontoPagado(float $v): static { $this->montoPagado = (string) $v; return $this; }

    public function getSaldoPendiente(): float
    {
        return max(0.0, $this->getMontoTotal() - $this->getMontoPagado());
    }

    /** Estado calculado — nunca persiste en BD. */
    public function getEstado(): string
    {
        if ($this->getMontoPagado() >= $this->getMontoTotal()) {
            return self::ESTADO_PAGADO;
        }
        if ($this->getMontoPagado() > 0) {
            return self::ESTADO_PARCIAL;
        }
        $hoy = new \DateTimeImmutable('today');
        if ($this->fechaVencimiento < $hoy) {
            return self::ESTADO_VENCIDO;
        }
        return self::ESTADO_PENDIENTE;
    }

    public function isPagada(): bool { return $this->getEstado() === self::ESTADO_PAGADO; }
    public function isVencida(): bool { return $this->getEstado() === self::ESTADO_VENCIDO; }
}
