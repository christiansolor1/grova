<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\CasaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CasaRepository::class)]
#[ORM\Table(name: 'res_casa')]
#[ORM\Index(columns: ['tenant_id', 'residencial_id'], name: 'idx_res_casa_tenant')]
#[ORM\Index(columns: ['estado'], name: 'idx_res_casa_estado')]
class Casa
{
    public const TIPOS = [
        'casa'        => 'Casa',
        'apartamento' => 'Apartamento',
    ];

    public const ESTADOS = [
        'habitada' => 'Habitada',
        'vacia'    => 'Vacía',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 20)]
    private string $numero = '';

    #[ORM\Column(length: 20)]
    private string $tipo = 'casa';

    #[ORM\ManyToOne(targetEntity: Manzana::class, inversedBy: 'casas')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Manzana $manzana = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $posicionX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $posicionY = null;

    #[ORM\Column(length: 20)]
    private string $estado = 'vacia';

    #[ORM\ManyToOne(targetEntity: Residencial::class, inversedBy: 'casas')]
    #[ORM\JoinColumn(nullable: false)]
    private Residencial $residencial;

    /** @var Collection<int, Persona> */
    #[ORM\OneToMany(targetEntity: Persona::class, mappedBy: 'casa')]
    private Collection $personas;

    public function __construct()
    {
        $this->personas = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNumero(): string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
    public function getTipoLabel(): string { return self::TIPOS[$this->tipo] ?? $this->tipo; }

    public function getManzana(): ?Manzana { return $this->manzana; }
    public function setManzana(?Manzana $manzana): static { $this->manzana = $manzana; return $this; }

    public function getPosicionX(): ?float { return $this->posicionX; }
    public function setPosicionX(?float $posicionX): static { $this->posicionX = $posicionX; return $this; }

    public function getPosicionY(): ?float { return $this->posicionY; }
    public function setPosicionY(?float $posicionY): static { $this->posicionY = $posicionY; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
    public function getEstadoLabel(): string { return self::ESTADOS[$this->estado] ?? $this->estado; }
    public function isHabitada(): bool { return $this->estado === 'habitada'; }

    public function getResidencial(): Residencial { return $this->residencial; }
    public function setResidencial(Residencial $residencial): static { $this->residencial = $residencial; return $this; }

    /** @return Collection<int, Persona> */
    public function getPersonas(): Collection { return $this->personas; }

    public function getPropietario(): ?Persona
    {
        foreach ($this->personas as $persona) {
            if ($persona->getTipo() === 'propietario' && $persona->isActiva()) {
                return $persona;
            }
        }
        return null;
    }

    public function getLabel(): string
    {
        $manzana = $this->manzana ? $this->manzana->getNombre() . ' — ' : '';
        return $manzana . 'Casa ' . $this->numero;
    }
}
