<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Líneas de bono extra (€) por factura; varias por mes. */
#[ORM\Entity]
#[ORM\Table(name: 'work_invoice_bonus_line')]
class WorkInvoiceBonusLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkInvoice::class, inversedBy: 'bonusLines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?WorkInvoice $invoice = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $importeEur = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $concepto = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?WorkInvoice
    {
        return $this->invoice;
    }

    public function setInvoice(?WorkInvoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getImporteEur(): string
    {
        return $this->importeEur;
    }

    public function getImporteFloat(): float
    {
        return (float) $this->importeEur;
    }

    public function setImporteEur(string $importeEur): static
    {
        $this->importeEur = $importeEur;

        return $this;
    }

    public function getConcepto(): ?string
    {
        return $this->concepto;
    }

    public function setConcepto(?string $concepto): static
    {
        $this->concepto = $concepto;

        return $this;
    }
}
