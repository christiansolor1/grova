<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\ManzanaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManzanaRepository::class)]
#[ORM\Table(name: 'res_manzana')]
#[ORM\Index(columns: ['tenant_id', 'residencial_id'], name: 'idx_res_manzana_tenant')]
class Manzana
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $posicionX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $posicionY = null;

    #[ORM\ManyToOne(targetEntity: Residencial::class, inversedBy: 'manzanas')]
    #[ORM\JoinColumn(nullable: false)]
    private Residencial $residencial;

    /** @var Collection<int, Casa> */
    #[ORM\OneToMany(targetEntity: Casa::class, mappedBy: 'manzana')]
    private Collection $casas;

    public function __construct()
    {
        $this->casas = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getPosicionX(): ?float { return $this->posicionX; }
    public function setPosicionX(?float $posicionX): static { $this->posicionX = $posicionX; return $this; }

    public function getPosicionY(): ?float { return $this->posicionY; }
    public function setPosicionY(?float $posicionY): static { $this->posicionY = $posicionY; return $this; }

    public function getResidencial(): Residencial { return $this->residencial; }
    public function setResidencial(Residencial $residencial): static { $this->residencial = $residencial; return $this; }

    /** @return Collection<int, Casa> */
    public function getCasas(): Collection { return $this->casas; }
}
