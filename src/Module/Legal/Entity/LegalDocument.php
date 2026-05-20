<?php

declare(strict_types=1);

namespace App\Module\Legal\Entity;

use App\Module\Legal\Repository\LegalDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegalDocumentRepository::class)]
#[ORM\Table(name: 'legal_document')]
class LegalDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: LegalCase::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LegalCase $case;

    #[ORM\Column(length: 150)]
    private string $nombre = '';

    /** Nombre del archivo guardado en public/uploads/legal/ */
    #[ORM\Column(length: 255)]
    private string $archivo = '';

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $extension = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCase(): LegalCase { return $this->case; }
    public function setCase(LegalCase $case): static { $this->case = $case; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getArchivo(): string { return $this->archivo; }
    public function setArchivo(string $archivo): static { $this->archivo = $archivo; return $this; }

    public function getArchivoUrl(): string { return '/uploads/legal/' . $this->archivo; }

    public function getExtension(): ?string { return $this->extension; }
    public function setExtension(?string $extension): static { $this->extension = $extension; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
