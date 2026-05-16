<?php

declare(strict_types=1);

namespace App\Module\Legal\Entity;

use App\Module\Core\Contact\Entity\Contact;
use App\Module\Legal\Repository\LegalCaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegalCaseRepository::class)]
#[ORM\Table(name: 'legal_case')]
#[ORM\Index(columns: ['estado'], name: 'idx_legal_case_estado')]
class LegalCase
{
    public const TIPOS = [
        'penal'          => 'Penal',
        'civil'          => 'Civil',
        'laboral'        => 'Laboral',
        'familiar'       => 'Familiar',
        'mercantil'      => 'Mercantil',
        'administrativo' => 'Administrativo',
        'otro'           => 'Otro',
    ];

    public const ESTADOS = [
        'abierto'    => 'Abierto',
        'en_proceso' => 'En proceso',
        'cerrado'    => 'Cerrado',
        'archivado'  => 'Archivado',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Contact $contact;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(length: 20)]
    private string $tipo = 'civil';

    #[ORM\Column(length: 20)]
    private string $estado = 'abierto';

    #[ORM\Column(length: 255)]
    private string $titulo = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaApertura;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaCierre = null;

    /** Honorarios acordados */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $honorarios = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, LegalFollowUp> */
    #[ORM\OneToMany(targetEntity: LegalFollowUp::class, mappedBy: 'case', cascade: ['remove'])]
    private Collection $followUps;

    /** @var Collection<int, LegalPayment> */
    #[ORM\OneToMany(targetEntity: LegalPayment::class, mappedBy: 'case', cascade: ['remove'])]
    private Collection $payments;

    /** @var Collection<int, LegalDocument> */
    #[ORM\OneToMany(targetEntity: LegalDocument::class, mappedBy: 'case', cascade: ['remove'])]
    private Collection $documents;

    public function __construct()
    {
        $this->fechaApertura = new \DateTimeImmutable();
        $this->createdAt     = new \DateTimeImmutable();
        $this->followUps     = new ArrayCollection();
        $this->payments      = new ArrayCollection();
        $this->documents     = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getContact(): Contact { return $this->contact; }
    public function setContact(Contact $contact): static { $this->contact = $contact; return $this; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(?string $numero): static { $this->numero = $numero; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
    public function getTipoLabel(): string { return self::TIPOS[$this->tipo] ?? $this->tipo; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
    public function getEstadoLabel(): string { return self::ESTADOS[$this->estado] ?? $this->estado; }

    public function getTitulo(): string { return $this->titulo; }
    public function setTitulo(string $titulo): static { $this->titulo = $titulo; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getFechaApertura(): \DateTimeImmutable { return $this->fechaApertura; }
    public function setFechaApertura(\DateTimeImmutable $fecha): static { $this->fechaApertura = $fecha; return $this; }

    public function getFechaCierre(): ?\DateTimeImmutable { return $this->fechaCierre; }
    public function setFechaCierre(?\DateTimeImmutable $fecha): static { $this->fechaCierre = $fecha; return $this; }

    public function getHonorarios(): ?string { return $this->honorarios; }
    public function getHonorariosFloat(): float { return (float) ($this->honorarios ?? 0); }
    public function setHonorarios(?string $honorarios): static { $this->honorarios = $honorarios; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, LegalFollowUp> */
    public function getFollowUps(): Collection { return $this->followUps; }

    /** @return Collection<int, LegalPayment> */
    public function getPayments(): Collection { return $this->payments; }

    /** @return Collection<int, LegalDocument> */
    public function getDocuments(): Collection { return $this->documents; }

    public function getTotalCobrado(): float
    {
        return array_sum($this->payments->filter(fn($p) => $p->getEstado() === 'pagado')->map(fn($p) => $p->getMonto())->toArray());
    }

    public function getSaldoPendiente(): float
    {
        return $this->getHonorariosFloat() - $this->getTotalCobrado();
    }

    public function isAbierto(): bool { return in_array($this->estado, ['abierto', 'en_proceso'], true); }
}
