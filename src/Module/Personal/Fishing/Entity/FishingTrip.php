<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingTripRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingTripRepository::class)]
#[ORM\Table(name: 'fishing_trip')]
#[ORM\Index(columns: ['fecha'], name: 'idx_fishing_trip_fecha')]
class FishingTrip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: FishingFinca::class)]
    #[ORM\JoinColumn(nullable: false)]
    private FishingFinca $finca;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, FishingTripMember> */
    #[ORM\OneToMany(targetEntity: FishingTripMember::class, mappedBy: 'trip', cascade: ['persist', 'remove'])]
    private Collection $members;

    /** @var Collection<int, FishingExpense> */
    #[ORM\OneToMany(targetEntity: FishingExpense::class, mappedBy: 'trip', cascade: ['persist', 'remove'])]
    private Collection $expenses;

    /** @var Collection<int, FishingTripLure> */
    #[ORM\OneToMany(targetEntity: FishingTripLure::class, mappedBy: 'trip', cascade: ['persist', 'remove'])]
    private Collection $lures;

    public function __construct()
    {
        $this->fecha     = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->members   = new ArrayCollection();
        $this->expenses  = new ArrayCollection();
        $this->lures     = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getFinca(): FishingFinca { return $this->finca; }
    public function setFinca(FishingFinca $finca): static { $this->finca = $finca; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static { $this->fecha = $fecha; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, FishingTripMember> */
    public function getMembers(): Collection { return $this->members; }

    /** @return Collection<int, FishingExpense> */
    public function getExpenses(): Collection { return $this->expenses; }

    /** @return Collection<int, FishingTripLure> */
    public function getLures(): Collection { return $this->lures; }

    public function getMemberCount(): int { return $this->members->count(); }

    public function getTotalGastos(): float
    {
        return array_sum($this->expenses->map(fn($e) => $e->getMonto())->toArray());
    }

    public function getGastoPorMiembro(): float
    {
        $count = $this->getMemberCount();
        return $count > 0 ? round($this->getTotalGastos() / $count, 2) : 0.0;
    }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
