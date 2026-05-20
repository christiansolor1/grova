<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LicenciaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenciaRepository::class)]
#[ORM\Table(name: 'licencia')]
class Licencia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    /** El string base64 de la licencia firmada — se entrega al cliente */
    #[ORM\Column(type: 'text')]
    private string $clave;

    /** activa | revocada */
    #[ORM\Column(length: 20)]
    private string $estado = 'activa';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $fechaEmision;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $fechaVencimiento;

    #[ORM\Column]
    private int $duracionDias;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $modulos = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    public function getId(): ?int { return $this->id; }

    public function getTenant(): Tenant { return $this->tenant; }
    public function setTenant(Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getClave(): string { return $this->clave; }
    public function setClave(string $clave): static { $this->clave = $clave; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }

    public function getFechaEmision(): \DateTimeImmutable { return $this->fechaEmision; }
    public function setFechaEmision(\DateTimeImmutable $fechaEmision): static { $this->fechaEmision = $fechaEmision; return $this; }

    public function getFechaVencimiento(): \DateTimeImmutable { return $this->fechaVencimiento; }
    public function setFechaVencimiento(\DateTimeImmutable $fechaVencimiento): static { $this->fechaVencimiento = $fechaVencimiento; return $this; }

    public function getDuracionDias(): int { return $this->duracionDias; }
    public function setDuracionDias(int $duracionDias): static { $this->duracionDias = $duracionDias; return $this; }

    /** @return list<string> */
    public function getModulos(): array { return $this->modulos; }

    /** @param list<string> $modulos */
    public function setModulos(array $modulos): static { $this->modulos = $modulos; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function isActiva(): bool { return $this->estado === 'activa'; }

    public function estaVigente(): bool
    {
        return $this->isActiva() && $this->fechaVencimiento >= new \DateTimeImmutable('today');
    }
}
