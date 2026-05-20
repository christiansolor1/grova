<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SuscripcionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuscripcionRepository::class)]
#[ORM\Table(name: 'suscripcion')]
class Suscripcion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: Plan::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Plan $plan;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaInicio;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaVencimiento;

    /** activa | vencida | cancelada */
    #[ORM\Column(length: 20)]
    private string $estado = 'activa';

    /** cortesia | pago — clasificación para métricas de negocio */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tipoCliente = null;

    public function getId(): ?int { return $this->id; }

    public function getTenant(): Tenant { return $this->tenant; }
    public function setTenant(Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getPlan(): Plan { return $this->plan; }
    public function setPlan(Plan $plan): static { $this->plan = $plan; return $this; }

    public function getFechaInicio(): \DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(\DateTimeImmutable $fechaInicio): static { $this->fechaInicio = $fechaInicio; return $this; }

    public function getFechaVencimiento(): \DateTimeImmutable { return $this->fechaVencimiento; }
    public function setFechaVencimiento(\DateTimeImmutable $fechaVencimiento): static { $this->fechaVencimiento = $fechaVencimiento; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }

    public function isActiva(): bool { return $this->estado === 'activa'; }

    public function getTipoCliente(): ?string { return $this->tipoCliente; }
    public function setTipoCliente(?string $tipoCliente): static { $this->tipoCliente = $tipoCliente; return $this; }
}
