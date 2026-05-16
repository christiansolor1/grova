<?php

declare(strict_types=1);

namespace App\Module\Personal\Fishing\Entity;

use App\Module\Personal\Fishing\Repository\FishingLureResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FishingLureResultRepository::class)]
#[ORM\Table(name: 'fishing_lure_result')]
class FishingLureResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FishingLure::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FishingLure $lure;

    #[ORM\ManyToOne(targetEntity: FishingFinca::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FishingFinca $finca;

    /** true = funcionó, false = no funcionó */
    #[ORM\Column]
    private bool $funciono = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    /** ID del usuario que registró esto (cross-DB, sin FK) */
    #[ORM\Column(nullable: true)]
    private ?int $registradoPorUserId = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getLure(): FishingLure { return $this->lure; }
    public function setLure(FishingLure $lure): static { $this->lure = $lure; return $this; }

    public function getFinca(): FishingFinca { return $this->finca; }
    public function setFinca(FishingFinca $finca): static { $this->finca = $finca; return $this; }

    public function isFunciono(): bool { return $this->funciono; }
    public function setFunciono(bool $funciono): static { $this->funciono = $funciono; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getRegistradoPorUserId(): ?int { return $this->registradoPorUserId; }
    public function setRegistradoPorUserId(?int $id): static { $this->registradoPorUserId = $id; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
