<?php

declare(strict_types=1);

namespace App\Module\Construccion\Entity;

use App\Module\Core\Contact\Entity\Contact;
use App\Module\Construccion\Repository\ConstruccionObraRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConstruccionObraRepository::class)]
#[ORM\Table(name: 'construccion_obra')]
#[ORM\Index(columns: ['estado'], name: 'idx_obra_estado')]
class ConstruccionObra
{
    public const ESTADOS = [
        'borrador'   => 'Borrador',
        'activa'     => 'Activa',
        'pausada'    => 'Pausada',
        'completada' => 'Completada',
        'cancelada'  => 'Cancelada',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 255)]
    private string $nombre = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    /** Cliente o dueño de la obra */
    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contact $cliente = null;

    /** Nombre de referencia si no hay contacto */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clienteNombre = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $presupuesto = null;

    #[ORM\Column(length: 20)]
    private string $estado = 'activa';

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaInicio = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaFin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, ConstruccionGasto> */
    #[ORM\OneToMany(targetEntity: ConstruccionGasto::class, mappedBy: 'obra', cascade: ['remove'])]
    #[ORM\OrderBy(['fecha' => 'DESC'])]
    private Collection $gastos;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->gastos    = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $d): static { $this->descripcion = $d; return $this; }

    public function getCliente(): ?Contact { return $this->cliente; }
    public function setCliente(?Contact $c): static { $this->cliente = $c; return $this; }

    public function getClienteNombre(): ?string { return $this->clienteNombre; }
    public function setClienteNombre(?string $n): static { $this->clienteNombre = $n; return $this; }

    public function getClienteDisplay(): string
    {
        if ($this->cliente !== null) {
            return $this->cliente->getNombreCompleto();
        }
        return $this->clienteNombre ?? '—';
    }

    public function getPresupuesto(): ?string { return $this->presupuesto; }
    public function getPresupuestoFloat(): float { return (float) ($this->presupuesto ?? 0); }
    public function setPresupuesto(?string $p): static { $this->presupuesto = $p; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $e): static { $this->estado = $e; return $this; }
    public function getEstadoLabel(): string { return self::ESTADOS[$this->estado] ?? $this->estado; }

    public function getFechaInicio(): ?\DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(?\DateTimeImmutable $f): static { $this->fechaInicio = $f; return $this; }

    public function getFechaFin(): ?\DateTimeImmutable { return $this->fechaFin; }
    public function setFechaFin(?\DateTimeImmutable $f): static { $this->fechaFin = $f; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $n): static { $this->notas = $n; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, ConstruccionGasto> */
    public function getGastos(): Collection { return $this->gastos; }

    public function getTotalGastos(): float
    {
        return array_sum($this->gastos->map(fn($g) => $g->getMonto())->toArray());
    }

    public function getSaldoDisponible(): float
    {
        return $this->getPresupuestoFloat() - $this->getTotalGastos();
    }

    public function getPorcentajeEjecutado(): float
    {
        $presupuesto = $this->getPresupuestoFloat();
        if ($presupuesto <= 0) return 0;
        return min(100, ($this->getTotalGastos() / $presupuesto) * 100);
    }

    public function isActiva(): bool
    {
        return in_array($this->estado, ['activa', 'pausada'], true);
    }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
