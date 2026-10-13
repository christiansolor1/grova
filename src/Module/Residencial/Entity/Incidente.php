<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\IncidenteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncidenteRepository::class)]
#[ORM\Table(name: 'res_incidente')]
#[ORM\Index(columns: ['tenant_id', 'tipo'], name: 'idx_res_incidente_tenant_tipo')]
class Incidente
{
    public const TIPOS = [
        'robo'       => 'Robo',
        'accidente'  => 'Accidente',
        'pelea'      => 'Pelea',
        'sospechoso' => 'Persona sospechosa',
        'otro'       => 'Otro',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 20)]
    private string $tipo = 'otro';

    #[ORM\Column(type: 'text')]
    private string $descripcion = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foto = null;

    #[ORM\ManyToOne(targetEntity: Persona::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Persona $guardia;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
    public function getTipoLabel(): string { return self::TIPOS[$this->tipo] ?? $this->tipo; }

    public function getDescripcion(): string { return $this->descripcion; }
    public function setDescripcion(string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getFoto(): ?string { return $this->foto; }
    public function setFoto(?string $foto): static { $this->foto = $foto; return $this; }

    public function getGuardia(): Persona { return $this->guardia; }
    public function setGuardia(Persona $guardia): static { $this->guardia = $guardia; return $this; }

    public function getCreadoEn(): \DateTimeImmutable { return $this->creadoEn; }
}
