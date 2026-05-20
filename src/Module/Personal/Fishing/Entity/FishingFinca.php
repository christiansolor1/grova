<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingFincaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingFincaRepository::class)]
#[ORM\Table(name: 'fishing_finca')]
class FishingFinca
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitud = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitud = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, FishingSpot> */
    #[ORM\OneToMany(targetEntity: FishingSpot::class, mappedBy: 'finca', cascade: ['remove'])]
    private Collection $spots;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->spots     = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getLatitud(): ?string { return $this->latitud; }
    public function setLatitud(?string $latitud): static { $this->latitud = $latitud; return $this; }

    public function getLongitud(): ?string { return $this->longitud; }
    public function setLongitud(?string $longitud): static { $this->longitud = $longitud; return $this; }

    public function hasCoords(): bool { return $this->latitud !== null && $this->longitud !== null; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, FishingSpot> */
    public function getSpots(): Collection { return $this->spots; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
