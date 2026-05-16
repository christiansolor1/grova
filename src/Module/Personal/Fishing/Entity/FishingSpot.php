<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingSpotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingSpotRepository::class)]
#[ORM\Table(name: 'fishing_spot')]
class FishingSpot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FishingFinca::class, inversedBy: 'spots')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FishingFinca $finca;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitud = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitud = null;

    #[ORM\Column(nullable: true)]
    private ?float $profundidadM = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getFinca(): FishingFinca { return $this->finca; }
    public function setFinca(FishingFinca $finca): static { $this->finca = $finca; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getLatitud(): ?string { return $this->latitud; }
    public function setLatitud(?string $latitud): static { $this->latitud = $latitud; return $this; }

    public function getLongitud(): ?string { return $this->longitud; }
    public function setLongitud(?string $longitud): static { $this->longitud = $longitud; return $this; }

    public function hasCoords(): bool { return $this->latitud !== null && $this->longitud !== null; }

    public function getProfundidadM(): ?float { return $this->profundidadM; }
    public function setProfundidadM(?float $profundidadM): static { $this->profundidadM = $profundidadM; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
