<?php

declare(strict_types=1);

namespace App\Module\Legal\Entity;

use App\Module\Legal\Repository\LegalPaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LegalPaymentRepository::class)]
#[ORM\Table(name: 'legal_payment')]
class LegalPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LegalCase::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LegalCase $case;

    #[ORM\Column(length: 150)]
    private string $concepto = '';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $monto = 0.0;

    /** pendiente | pagado */
    #[ORM\Column(length: 20)]
    private string $estado = 'pendiente';

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaPago = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCase(): LegalCase { return $this->case; }
    public function setCase(LegalCase $case): static { $this->case = $case; return $this; }

    public function getConcepto(): string { return $this->concepto; }
    public function setConcepto(string $concepto): static { $this->concepto = $concepto; return $this; }

    public function getMonto(): float { return $this->monto; }
    public function setMonto(float $monto): static { $this->monto = $monto; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
    public function isPagado(): bool { return $this->estado === 'pagado'; }

    public function getFechaPago(): ?\DateTimeImmutable { return $this->fechaPago; }
    public function setFechaPago(?\DateTimeImmutable $fechaPago): static { $this->fechaPago = $fechaPago; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
