<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinPrestamoRepository;
use Doctrine\ORM\Mapping as ORM;

/** Amortización de un préstamo (sistema francés). Extiende un FinCompromiso 1:1. */
#[ORM\Entity(repositoryClass: FinPrestamoRepository::class)]
#[ORM\Table(name: 'fin_prestamo')]
class FinPrestamo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: FinCompromiso::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FinCompromiso $compromiso;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $capital;

    /** Tasa nominal anual (ej: 0.18 = 18%). */
    #[ORM\Column(type: 'decimal', precision: 7, scale: 4)]
    private string $tasaAnual;

    #[ORM\Column(type: 'smallint')]
    private int $plazoMeses;

    /** Cuota fija calculada por sistema francés. */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $cuotaFija;

    /** Cargos adicionales por cuota (seguro, comisión). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, options: ['default' => 0])]
    private string $cargoExtraCuota = '0.00';

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaInicio;

    public function getId(): ?int { return $this->id; }

    public function getCompromiso(): FinCompromiso { return $this->compromiso; }
    public function setCompromiso(FinCompromiso $v): static { $this->compromiso = $v; return $this; }

    public function getCapital(): float { return (float) $this->capital; }
    public function setCapital(float $v): static { $this->capital = (string) $v; return $this; }

    public function getTasaAnual(): float { return (float) $this->tasaAnual; }
    public function setTasaAnual(float $v): static { $this->tasaAnual = (string) $v; return $this; }

    public function getPlazoMeses(): int { return $this->plazoMeses; }
    public function setPlazoMeses(int $v): static { $this->plazoMeses = $v; return $this; }

    public function getCuotaFija(): float { return (float) $this->cuotaFija; }
    public function setCuotaFija(float $v): static { $this->cuotaFija = (string) $v; return $this; }

    public function getCargoExtraCuota(): float { return (float) $this->cargoExtraCuota; }
    public function setCargoExtraCuota(float $v): static { $this->cargoExtraCuota = (string) $v; return $this; }

    public function getFechaInicio(): \DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(\DateTimeImmutable $v): static { $this->fechaInicio = $v; return $this; }

    /** Calcula la cuota fija francesa: P × [i(1+i)^n] / [(1+i)^n − 1] */
    public static function calcularCuotaFija(float $capital, float $tasaAnual, int $plazoMeses): float
    {
        if ($tasaAnual == 0) {
            return round($capital / $plazoMeses, 2);
        }
        $i = $tasaAnual / 12;
        $cuota = $capital * ($i * (1 + $i) ** $plazoMeses) / ((1 + $i) ** $plazoMeses - 1);
        return round($cuota, 2);
    }

    /**
     * Genera la tabla de amortización completa.
     * @return array<int, array{numero:int, fecha:string, capital:float, interes:float, saldo:float}>
     */
    public function generarAmortizacion(): array
    {
        $tabla      = [];
        $saldo      = $this->getCapital();
        $i          = $this->getTasaAnual() / 12;
        $cuota      = $this->getCuotaFija();
        $fecha      = $this->fechaInicio;

        for ($n = 1; $n <= $this->plazoMeses; $n++) {
            $interes  = round($saldo * $i, 2);
            $capital  = round($cuota - $interes, 2);
            $saldo    = round($saldo - $capital, 2);
            if ($n === $this->plazoMeses) {
                $capital += $saldo; // ajuste por redondeo
                $saldo    = 0.0;
            }
            $tabla[] = [
                'numero'   => $n,
                'fecha'    => $fecha->format('Y-m-d'),
                'capital'  => $capital,
                'interes'  => $interes,
                'otros'    => $this->getCargoExtraCuota(),
                'total'    => round($cuota + $this->getCargoExtraCuota(), 2),
                'saldo'    => $saldo,
            ];
            $fecha = $fecha->modify('+1 month');
        }

        return $tabla;
    }
}
