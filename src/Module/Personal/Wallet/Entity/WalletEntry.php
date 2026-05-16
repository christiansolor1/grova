<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Entity;

use App\Module\Personal\Wallet\Repository\WalletEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletEntryRepository::class)]
#[ORM\Table(name: 'wallet_entry')]
#[ORM\Index(columns: ['fecha'], name: 'idx_entry_fecha')]
#[ORM\Index(columns: ['tipo'], name: 'idx_entry_tipo')]
class WalletEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WalletCategory::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WalletCategory $category = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $monto = '0.00';

    /** ingreso | gasto */
    #[ORM\Column(length: 10)]
    private string $tipo = 'gasto';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->fecha     = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCategory(): ?WalletCategory { return $this->category; }
    public function setCategory(?WalletCategory $category): static { $this->category = $category; return $this; }

    public function getMonto(): string { return $this->monto; }
    public function setMonto(string $monto): static { $this->monto = $monto; return $this; }
    public function getMontoFloat(): float { return (float) $this->monto; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static { $this->fecha = $fecha; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isIngreso(): bool { return $this->tipo === 'ingreso'; }
    public function isGasto(): bool { return $this->tipo === 'gasto'; }

    public function getMontoSignado(): float
    {
        return $this->isGasto() ? -abs($this->getMontoFloat()) : abs($this->getMontoFloat());
    }
}
