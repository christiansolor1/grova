<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanRepository::class)]
#[ORM\Table(name: 'plan')]
class Plan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $nombre = '';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $modulos = [];

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $precioMensual = '0.00';

    #[ORM\Column(length: 20)]
    private string $estado = 'activo';

    public function getId(): ?int { return $this->id; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    /** @return list<string> */
    public function getModulos(): array { return $this->modulos; }

    /** @param list<string> $modulos */
    public function setModulos(array $modulos): static { $this->modulos = $modulos; return $this; }

    public function getPrecioMensual(): string { return $this->precioMensual; }
    public function setPrecioMensual(string $precioMensual): static { $this->precioMensual = $precioMensual; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }

    public function hasModulo(string $key): bool
    {
        return \in_array($key, $this->modulos, true);
    }
}
