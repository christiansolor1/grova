<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Entity;

use App\Module\Personal\Wallet\Repository\WalletCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletCategoryRepository::class)]
#[ORM\Table(name: 'wallet_category')]
class WalletCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $nombre = '';

    /** ingreso | gasto */
    #[ORM\Column(length: 10)]
    private string $tipo = 'gasto';

    #[ORM\Column(length: 60)]
    private string $icono = 'bi-tag';

    #[ORM\Column(length: 7)]
    private string $color = '#64748b';

    public function getId(): ?int { return $this->id; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }

    public function getIcono(): string { return $this->icono; }
    public function setIcono(string $icono): static { $this->icono = $icono; return $this; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $color): static { $this->color = $color; return $this; }

    public function isIngreso(): bool { return $this->tipo === 'ingreso'; }
    public function isGasto(): bool { return $this->tipo === 'gasto'; }
}
