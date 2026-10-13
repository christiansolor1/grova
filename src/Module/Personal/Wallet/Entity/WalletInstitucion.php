<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Entity;

use App\Module\Personal\Wallet\Repository\WalletInstitucionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletInstitucionRepository::class)]
#[ORM\Table(name: 'wallet_institucion')]
class WalletInstitucion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 100)]
    private string $nombre;

    /** Color de marca de la institución */
    #[ORM\Column(length: 7)]
    private string $color = '#64748b';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $color): static { $this->color = $color; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function __toString(): string { return $this->nombre; }
}
