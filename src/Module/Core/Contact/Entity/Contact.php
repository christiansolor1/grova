<?php

declare(strict_types=1);

namespace App\Module\Core\Contact\Entity;

use App\Module\Core\Contact\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'contact')]
#[ORM\Index(columns: ['tipo'], name: 'idx_contact_tipo')]
#[ORM\Index(columns: ['apellido', 'nombre'], name: 'idx_contact_nombre')]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    /** cliente | proveedor | empleado | paciente | otro */
    #[ORM\Column(length: 20)]
    private string $tipo = 'cliente';

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $apellido = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $empresa = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ciudad = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $pais = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getApellido(): ?string { return $this->apellido; }
    public function setApellido(?string $apellido): static { $this->apellido = $apellido; return $this; }

    public function getNombreCompleto(): string
    {
        return trim($this->nombre . ' ' . ($this->apellido ?? ''));
    }

    public function getInitials(): string
    {
        $parts = array_filter([$this->nombre, $this->apellido]);
        return strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), $parts)));
    }

    public function getEmpresa(): ?string { return $this->empresa; }
    public function setEmpresa(?string $empresa): static { $this->empresa = $empresa; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $telefono): static { $this->telefono = $telefono; return $this; }

    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $direccion): static { $this->direccion = $direccion; return $this; }

    public function getCiudad(): ?string { return $this->ciudad; }
    public function setCiudad(?string $ciudad): static { $this->ciudad = $ciudad; return $this; }

    public function getPais(): ?string { return $this->pais; }
    public function setPais(?string $pais): static { $this->pais = $pais; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $activo): static { $this->activo = $activo; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
