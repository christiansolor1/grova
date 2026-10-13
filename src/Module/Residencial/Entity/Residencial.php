<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\ResidencialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResidencialRepository::class)]
#[ORM\Table(name: 'res_residencial')]
#[ORM\Index(columns: ['tenant_id', 'estado'], name: 'idx_res_residencial_tenant_estado')]
class Residencial
{
    public const ESTADOS = [
        'activa'   => 'Activa',
        'inactiva' => 'Inactiva',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 255)]
    private string $nombre = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(nullable: true)]
    private ?int $cantidadCasas = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $coordenadasLat = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $coordenadasLng = null;

    #[ORM\Column(length: 20)]
    private string $estado = 'activa';

    /** JSON con posiciones de manzanas y casas en el mapa SVG */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $mapaJson = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Manzana> */
    #[ORM\OneToMany(targetEntity: Manzana::class, mappedBy: 'residencial', cascade: ['remove'])]
    private Collection $manzanas;

    /** @var Collection<int, Casa> */
    #[ORM\OneToMany(targetEntity: Casa::class, mappedBy: 'residencial', cascade: ['remove'])]
    private Collection $casas;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->manzanas  = new ArrayCollection();
        $this->casas     = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $direccion): static { $this->direccion = $direccion; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $telefono): static { $this->telefono = $telefono; return $this; }

    public function getCantidadCasas(): ?int { return $this->cantidadCasas; }
    public function setCantidadCasas(?int $cantidadCasas): static { $this->cantidadCasas = $cantidadCasas; return $this; }

    public function getCoordenadasLat(): ?string { return $this->coordenadasLat; }
    public function setCoordenadasLat(?string $lat): static { $this->coordenadasLat = $lat; return $this; }

    public function getCoordenadasLng(): ?string { return $this->coordenadasLng; }
    public function setCoordenadasLng(?string $lng): static { $this->coordenadasLng = $lng; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
    public function getEstadoLabel(): string { return self::ESTADOS[$this->estado] ?? $this->estado; }
    public function isActiva(): bool { return $this->estado === 'activa'; }

    public function getMapaJson(): ?array { return $this->mapaJson; }
    public function setMapaJson(?array $mapaJson): static { $this->mapaJson = $mapaJson; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, Manzana> */
    public function getManzanas(): Collection { return $this->manzanas; }

    /** @return Collection<int, Casa> */
    public function getCasas(): Collection { return $this->casas; }
}
