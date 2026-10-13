<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\PersonaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonaRepository::class)]
#[ORM\Table(name: 'res_persona')]
#[ORM\Index(columns: ['tenant_id', 'tipo'], name: 'idx_res_persona_tenant_tipo')]
#[ORM\Index(columns: ['user_id'], name: 'idx_res_persona_user')]
class Persona
{
    public const TIPOS = [
        'propietario'        => 'Propietario',
        'inquilino'          => 'Inquilino',
        'familiar'           => 'Familiar',
        'personal_recurrente'=> 'Personal recurrente',
    ];

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

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 30)]
    private string $tipo = 'propietario';

    #[ORM\ManyToOne(targetEntity: Casa::class, inversedBy: 'personas')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Casa $casa = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foto = null;

    #[ORM\Column(length: 20)]
    private string $estado = 'activo';

    /** FK al User del sistema para quienes tienen acceso web */
    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
    public function getTipoLabel(): string { return self::TIPOS[$this->tipo] ?? $this->tipo; }
    public function isPropietario(): bool { return $this->tipo === 'propietario'; }
    public function isGuardia(): bool { return $this->tipo === 'personal_recurrente'; }

    public function getCasa(): ?Casa { return $this->casa; }
    public function setCasa(?Casa $casa): static { $this->casa = $casa; return $this; }

    public function getFoto(): ?string { return $this->foto; }
    public function setFoto(?string $foto): static { $this->foto = $foto; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
    public function isActiva(): bool { return $this->estado === 'activo'; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): static { $this->userId = $userId; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
