<?php

declare(strict_types=1);

namespace App\Module\Construccion\Entity;

use App\Module\Construccion\Repository\ConstruccionGastoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConstruccionGastoRepository::class)]
#[ORM\Table(name: 'construccion_gasto')]
#[ORM\Index(columns: ['obra_id', 'fecha'], name: 'idx_gasto_obra_fecha')]
class ConstruccionGasto
{
    public const CATEGORIAS = [
        'material'     => 'Material',
        'mano_obra'    => 'Mano de obra',
        'maquinaria'   => 'Maquinaria',
        'transporte'   => 'Transporte',
        'herramienta'  => 'Herramienta',
        'permiso'      => 'Permiso / Trámite',
        'servicio'     => 'Servicio / Subcontrato',
        'otro'         => 'Otro',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'pagado'    => 'Pagado',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ConstruccionObra::class, inversedBy: 'gastos')]
    #[ORM\JoinColumn(nullable: false)]
    private ConstruccionObra $obra;

    #[ORM\ManyToOne(targetEntity: ConstruccionProveedor::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ConstruccionProveedor $proveedor = null;

    #[ORM\Column(length: 20)]
    private string $categoria = 'material';

    #[ORM\Column(length: 255)]
    private string $descripcion = '';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monto = '0.00';

    #[ORM\Column(length: 20)]
    private string $estado = 'pendiente';

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->fecha     = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getObra(): ConstruccionObra { return $this->obra; }
    public function setObra(ConstruccionObra $obra): static { $this->obra = $obra; return $this; }

    public function getProveedor(): ?ConstruccionProveedor { return $this->proveedor; }
    public function setProveedor(?ConstruccionProveedor $p): static { $this->proveedor = $p; return $this; }

    public function getCategoria(): string { return $this->categoria; }
    public function setCategoria(string $c): static { $this->categoria = $c; return $this; }
    public function getCategoriaLabel(): string { return self::CATEGORIAS[$this->categoria] ?? $this->categoria; }

    public function getDescripcion(): string { return $this->descripcion; }
    public function setDescripcion(string $d): static { $this->descripcion = $d; return $this; }

    public function getMonto(): float { return (float) $this->monto; }
    public function getMontoRaw(): string { return $this->monto; }
    public function setMonto(float $m): static { $this->monto = number_format($m, 2, '.', ''); return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $e): static { $this->estado = $e; return $this; }
    public function getEstadoLabel(): string { return self::ESTADOS[$this->estado] ?? $this->estado; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $f): static { $this->fecha = $f; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $n): static { $this->notas = $n; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isPagado(): bool { return $this->estado === 'pagado'; }
}
