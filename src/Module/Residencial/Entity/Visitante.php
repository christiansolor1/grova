<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\VisitanteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitanteRepository::class)]
#[ORM\Table(name: 'res_visitante')]
#[ORM\Index(columns: ['tenant_id', 'dni'], name: 'idx_res_visitante_tenant_dni')]
class Visitante
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 255)]
    private string $nombreCompleto = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $dni = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telefono = null;

    /** Foto capturada en la primera visita */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foto = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNombreCompleto(): string { return $this->nombreCompleto; }
    public function setNombreCompleto(string $nombreCompleto): static { $this->nombreCompleto = $nombreCompleto; return $this; }

    public function getDni(): ?string { return $this->dni; }
    public function setDni(?string $dni): static { $this->dni = $dni; return $this; }

    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $telefono): static { $this->telefono = $telefono; return $this; }

    public function getFoto(): ?string { return $this->foto; }
    public function setFoto(?string $foto): static { $this->foto = $foto; return $this; }

    public function getCreadoEn(): \DateTimeImmutable { return $this->creadoEn; }
}
