<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingExpenseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingExpenseRepository::class)]
#[ORM\Table(name: 'fishing_expense')]
class FishingExpense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FishingTrip::class, inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FishingTrip $trip;

    #[ORM\Column(length: 100)]
    private string $concepto = '';

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private float $monto = 0.0;

    /** Nombre de quien pagó */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pagadoPor = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTrip(): FishingTrip { return $this->trip; }
    public function setTrip(FishingTrip $trip): static { $this->trip = $trip; return $this; }

    public function getConcepto(): string { return $this->concepto; }
    public function setConcepto(string $concepto): static { $this->concepto = $concepto; return $this; }

    public function getMonto(): float { return $this->monto; }
    public function setMonto(float $monto): static { $this->monto = $monto; return $this; }

    public function getPagadoPor(): ?string { return $this->pagadoPor; }
    public function setPagadoPor(?string $pagadoPor): static { $this->pagadoPor = $pagadoPor; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
