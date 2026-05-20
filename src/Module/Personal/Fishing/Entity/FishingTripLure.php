<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingTripLureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingTripLureRepository::class)]
#[ORM\Table(name: 'fishing_trip_lure')]
class FishingTripLure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: FishingTrip::class, inversedBy: 'lures')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FishingTrip $trip;

    #[ORM\ManyToOne(targetEntity: FishingLure::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FishingLure $lure;

    #[ORM\Column]
    private bool $funciono = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    public function getId(): ?int { return $this->id; }

    public function getTrip(): FishingTrip { return $this->trip; }
    public function setTrip(FishingTrip $trip): static { $this->trip = $trip; return $this; }

    public function getLure(): FishingLure { return $this->lure; }
    public function setLure(FishingLure $lure): static { $this->lure = $lure; return $this; }

    public function isFunciono(): bool { return $this->funciono; }
    public function setFunciono(bool $funciono): static { $this->funciono = $funciono; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
