<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingTripMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingTripMemberRepository::class)]
#[ORM\Table(name: 'fishing_trip_member')]
#[ORM\UniqueConstraint(columns: ['tenant_id', 'trip_id', 'user_id'])]
class FishingTripMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: FishingTrip::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FishingTrip $trip;

    /** ID del usuario grova (cross-DB, sin FK) */
    #[ORM\Column]
    private int $userId;

    /** Nombre en caché para no tener que cruzar DBs siempre */
    #[ORM\Column(length: 100)]
    private string $nombre = '';

    public function getId(): ?int { return $this->id; }

    public function getTrip(): FishingTrip { return $this->trip; }
    public function setTrip(FishingTrip $trip): static { $this->trip = $trip; return $this; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): static { $this->userId = $userId; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
