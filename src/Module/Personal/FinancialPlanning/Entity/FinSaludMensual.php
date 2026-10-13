<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinSaludMensualRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Snapshot de salud financiera calculado al cierre de cada mes.
 * Indica si el mes fue positivo/neutro/negativo y cuánto quedó disponible.
 */
#[ORM\Entity(repositoryClass: FinSaludMensualRepository::class)]
#[ORM\Table(name: 'fin_salud_mensual')]
#[ORM\UniqueConstraint(name: 'uq_fin_salud_tenant_mes', columns: ['tenant_id', 'anio', 'mes'])]
class FinSaludMensual
{
    public const ESTADO_POSITIVO = 'positivo';
    public const ESTADO_NEUTRO   = 'neutro';
    public const ESTADO_NEGATIVO = 'negativo';

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

    /** Suma de ingresos netos HNL. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $ingresoNetoHnl = '0.00';

    /** Suma de todos los gastos variables HNL. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $gastoVariableHnl = '0.00';

    /** Suma de cuotas de compromisos del mes HNL. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $gastoFijoHnl = '0.00';

    /** Monto efectivamente ahorrado/invertido HNL. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $ahorroHnl = '0.00';

    /** Flujo neto = ingreso − gasto_variable − gasto_fijo − ahorro. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $flujonetoHnl = '0.00';

    /** positivo | neutro | negativo */
    #[ORM\Column(length: 10, options: ['default' => 'neutro'])]
    private string $estado = self::ESTADO_NEUTRO;

    /** Puntuación 0–100 calculada según reglas del plan (Babilonia/Harvard/libre). */
    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $puntuacion = 0;

    /** Porcentajes reales del mes (JSON: {necesidad, deseo, ahorro, deuda}). */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $porcentajesReales = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $calculadoAt;

    public function __construct()
    {
        $this->calculadoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getAnio(): int { return $this->anio; }
    public function setAnio(int $v): static { $this->anio = $v; return $this; }

    public function getMes(): int { return $this->mes; }
    public function setMes(int $v): static { $this->mes = $v; return $this; }

    public function getIngresoNetoHnl(): float { return (float) $this->ingresoNetoHnl; }
    public function setIngresoNetoHnl(float $v): static { $this->ingresoNetoHnl = (string) $v; return $this; }

    public function getGastoVariableHnl(): float { return (float) $this->gastoVariableHnl; }
    public function setGastoVariableHnl(float $v): static { $this->gastoVariableHnl = (string) $v; return $this; }

    public function getGastoFijoHnl(): float { return (float) $this->gastoFijoHnl; }
    public function setGastoFijoHnl(float $v): static { $this->gastoFijoHnl = (string) $v; return $this; }

    public function getAhorroHnl(): float { return (float) $this->ahorroHnl; }
    public function setAhorroHnl(float $v): static { $this->ahorroHnl = (string) $v; return $this; }

    public function getFlujonetoHnl(): float { return (float) $this->flujonetoHnl; }
    public function setFlujonetoHnl(float $v): static { $this->flujonetoHnl = (string) $v; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $v): static { $this->estado = $v; return $this; }

    public function getPuntuacion(): int { return $this->puntuacion; }
    public function setPuntuacion(int $v): static { $this->puntuacion = max(0, min(100, $v)); return $this; }

    public function getPorcentajesReales(): ?array { return $this->porcentajesReales; }
    public function setPorcentajesReales(?array $v): static { $this->porcentajesReales = $v; return $this; }

    public function getCalculadoAt(): \DateTimeImmutable { return $this->calculadoAt; }
    public function setCalculadoAt(\DateTimeImmutable $v): static { $this->calculadoAt = $v; return $this; }

    public function getTotalGastadoHnl(): float
    {
        return round($this->getGastoVariableHnl() + $this->getGastoFijoHnl(), 2);
    }
}
