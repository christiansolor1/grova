<?php

declare(strict_types=1);

namespace App\Module\Construccion\Entity;

use App\Module\Core\Contact\Entity\Contact;
use App\Module\Construccion\Repository\ConstruccionProveedorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConstruccionProveedorRepository::class)]
#[ORM\Table(name: 'construccion_proveedor')]
class ConstruccionProveedor
{
    public const ESPECIALIDADES = [
        'materiales'   => 'Materiales',
        'mano_obra'    => 'Mano de obra',
        'maquinaria'   => 'Maquinaria',
        'electrico'    => 'Eléctrico',
        'plomeria'     => 'Plomería',
        'carpinteria'  => 'Carpintería',
        'pintura'      => 'Pintura',
        'ceramica'     => 'Cerámica',
        'hierro'       => 'Hierro / Estructural',
        'transporte'   => 'Transporte',
        'otro'         => 'Otro',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nombre = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(length: 20)]
    private string $especialidad = 'materiales';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $n): static { $this->nombre = $n; return $this; }

    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $t): static { $this->telefono = $t; return $this; }

    public function getEspecialidad(): string { return $this->especialidad; }
    public function setEspecialidad(string $e): static { $this->especialidad = $e; return $this; }
    public function getEspecialidadLabel(): string { return self::ESPECIALIDADES[$this->especialidad] ?? $this->especialidad; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $n): static { $this->notas = $n; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $a): static { $this->activo = $a; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
