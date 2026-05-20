<?php

declare(strict_types=1);

namespace App\Module\Legal\Entity;

use App\Module\Legal\Repository\LegalFollowUpRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegalFollowUpRepository::class)]
#[ORM\Table(name: 'legal_follow_up')]
class LegalFollowUp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: LegalCase::class, inversedBy: 'followUps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LegalCase $case;

    #[ORM\Column(type: 'text')]
    private string $descripcion = '';

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $proximaAudiencia = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->fecha     = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCase(): LegalCase { return $this->case; }
    public function setCase(LegalCase $case): static { $this->case = $case; return $this; }

    public function getDescripcion(): string { return $this->descripcion; }
    public function setDescripcion(string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static { $this->fecha = $fecha; return $this; }

    public function getProximaAudiencia(): ?\DateTimeImmutable { return $this->proximaAudiencia; }
    public function setProximaAudiencia(?\DateTimeImmutable $proximaAudiencia): static { $this->proximaAudiencia = $proximaAudiencia; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
