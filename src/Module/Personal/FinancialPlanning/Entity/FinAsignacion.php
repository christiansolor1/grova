<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinAsignacionRepository;
use Doctrine\ORM\Mapping as ORM;

/** Asignación de monto por bolsa dentro de un presupuesto mensual. */
#[ORM\Entity(repositoryClass: FinAsignacionRepository::class)]
#[ORM\Table(name: 'fin_asignacion')]
#[ORM\UniqueConstraint(name: 'uq_fin_asignacion', columns: ['presupuesto_id', 'bolsa_id'])]
class FinAsignacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FinPresupuesto::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FinPresupuesto $presupuesto;

    #[ORM\ManyToOne(targetEntity: FinBolsa::class)]
    #[ORM\JoinColumn(nullable: false)]
    private FinBolsa $bolsa;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $porcentaje = '0.00';

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $montoAsignadoHnl = '0.00';

    public function getId(): ?int { return $this->id; }

    public function getPresupuesto(): FinPresupuesto { return $this->presupuesto; }
    public function setPresupuesto(FinPresupuesto $v): static { $this->presupuesto = $v; return $this; }

    public function getBolsa(): FinBolsa { return $this->bolsa; }
    public function setBolsa(FinBolsa $v): static { $this->bolsa = $v; return $this; }

    public function getPorcentaje(): float { return (float) $this->porcentaje; }
    public function setPorcentaje(float $v): static { $this->porcentaje = (string) $v; return $this; }

    public function getMontoAsignadoHnl(): float { return (float) $this->montoAsignadoHnl; }
    public function setMontoAsignadoHnl(float $v): static { $this->montoAsignadoHnl = (string) $v; return $this; }
}
