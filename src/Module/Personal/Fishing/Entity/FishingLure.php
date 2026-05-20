<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingLureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingLureRepository::class)]
#[ORM\Table(name: 'fishing_lure')]
class FishingLure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $marca = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $color = null;

    /** tipo: cuchara, popper, jig, señuelo blando, etc. */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tipo = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $precio = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $tienda = null;

    /** Nombre o identificador del propietario */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $propietario = null;

    /** ID del usuario grova propietario (cross-DB, sin FK) */
    #[ORM\Column(nullable: true)]
    private ?int $propietarioUserId = null;

    /** Nombre del archivo guardado en public/uploads/fishing/lures/ */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foto = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getMarca(): ?string { return $this->marca; }
    public function setMarca(?string $marca): static { $this->marca = $marca; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): static { $this->color = $color; return $this; }

    public function getTipo(): ?string { return $this->tipo; }
    public function setTipo(?string $tipo): static { $this->tipo = $tipo; return $this; }

    public function getPrecio(): ?string { return $this->precio; }
    public function getPrecioFloat(): float { return (float) ($this->precio ?? 0); }
    public function setPrecio(?string $precio): static { $this->precio = $precio; return $this; }

    public function getTienda(): ?string { return $this->tienda; }
    public function setTienda(?string $tienda): static { $this->tienda = $tienda; return $this; }

    public function getPropietario(): ?string { return $this->propietario; }
    public function setPropietario(?string $propietario): static { $this->propietario = $propietario; return $this; }

    public function getPropietarioUserId(): ?int { return $this->propietarioUserId; }
    public function setPropietarioUserId(?int $propietarioUserId): static { $this->propietarioUserId = $propietarioUserId; return $this; }

    public function getFoto(): ?string { return $this->foto; }
    public function setFoto(?string $foto): static { $this->foto = $foto; return $this; }

    public function getFotoUrl(): string
    {
        return $this->foto ? '/uploads/fishing/lures/' . $this->foto : '/img/lure-placeholder.png';
    }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
