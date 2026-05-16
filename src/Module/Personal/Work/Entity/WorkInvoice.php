<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Entity;

use App\Module\Personal\Work\Repository\WorkInvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkInvoiceRepository::class)]
#[ORM\Table(name: 'work_invoice')]
#[ORM\UniqueConstraint(columns: ['client_id', 'anio', 'mes'])]
class WorkInvoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkClient::class)]
    #[ORM\JoinColumn(nullable: false)]
    private WorkClient $client;

    #[ORM\Column]
    private int $anio;

    #[ORM\Column]
    private int $mes;

    #[ORM\Column]
    private int $diasTrabajados = 0;

    #[ORM\Column]
    private int $diasBonus = 0;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $salarioBase = '1100.00';

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $montoBonus = '0.00';

    /** Bonos extra en € (varias líneas), aparte del bonus por puntualidad ({@see self::$montoBonus}). */
    #[ORM\OneToMany(targetEntity: WorkInvoiceBonusLine::class, mappedBy: 'invoice', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $bonusLines;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $total = '0.00';

    /** Número de factura/recibo secuencial */
    #[ORM\Column(nullable: true)]
    private ?int $numero = null;

    /** borrador | enviada */
    #[ORM\Column(length: 20)]
    private string $estado = 'borrador';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enviadaAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pagadaAt = null;

    /** Comisión banco en L al acreditar este cobro (null = usar estimado del cliente al calcular). */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $comisionBancoHnl = null;

    /** Nº recibo / comisión SWIFT (referencia banco). */
    #[ORM\Column(nullable: true)]
    private ?int $reciboSwift = null;

    /** L por 1 € y L por 1 US$ al generar o recalcular la factura (panel). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 5, nullable: true)]
    private ?string $tasaEmisionEurL = null;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 5, nullable: true)]
    private ?string $tasaEmisionUsdL = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $tasaEmisionFecha = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $tasaEmisionSource = null;

    /** Tasas del panel al marcar pagada (o al guardar cobro). */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 5, nullable: true)]
    private ?string $tasaPagoEurL = null;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 5, nullable: true)]
    private ?string $tasaPagoUsdL = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $tasaPagoFecha = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $tasaPagoSource = null;

    /** Nombre interno del archivo (único en disco). */
    #[ORM\Column(name: 'payment_proof_stored_filename', length: 180, nullable: true)]
    private ?string $paymentProofStoredFilename = null;

    /** Nombre original subido por el usuario. */
    #[ORM\Column(name: 'payment_proof_original_name', length: 255, nullable: true)]
    private ?string $paymentProofOriginalName = null;

    #[ORM\Column(name: 'payment_proof_mime', length: 127, nullable: true)]
    private ?string $paymentProofMime = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $now             = new \DateTimeImmutable();
        $this->anio       = (int) $now->format('Y');
        $this->mes        = (int) $now->format('n');
        $this->createdAt  = $now;
        $this->bonusLines = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getClient(): WorkClient { return $this->client; }
    public function setClient(WorkClient $client): static { $this->client = $client; return $this; }

    public function getAnio(): int { return $this->anio; }
    public function setAnio(int $anio): static { $this->anio = $anio; return $this; }

    public function getMes(): int { return $this->mes; }
    public function setMes(int $mes): static { $this->mes = $mes; return $this; }

    public function getDiasTrabajados(): int { return $this->diasTrabajados; }
    public function setDiasTrabajados(int $diasTrabajados): static { $this->diasTrabajados = $diasTrabajados; return $this; }

    public function getDiasBonus(): int { return $this->diasBonus; }
    public function setDiasBonus(int $diasBonus): static { $this->diasBonus = $diasBonus; return $this; }

    public function getSalarioBase(): string { return $this->salarioBase; }
    public function getSalarioBaseFloat(): float { return (float) $this->salarioBase; }
    public function setSalarioBase(string $salarioBase): static { $this->salarioBase = $salarioBase; return $this; }

    public function getMontoBonus(): string { return $this->montoBonus; }
    public function getMontoBonusFloat(): float { return (float) $this->montoBonus; }
    public function setMontoBonus(string $montoBonus): static { $this->montoBonus = $montoBonus; return $this; }

    /** @return Collection<int, WorkInvoiceBonusLine> */
    public function getBonusLines(): Collection
    {
        return $this->bonusLines;
    }

    public function addBonusLine(WorkInvoiceBonusLine $line): static
    {
        if (!$this->bonusLines->contains($line)) {
            $this->bonusLines->add($line);
            $line->setInvoice($this);
        }

        return $this;
    }

    public function removeBonusLine(WorkInvoiceBonusLine $line): static
    {
        if ($this->bonusLines->removeElement($line) && $line->getInvoice() === $this) {
            $line->setInvoice(null);
        }

        return $this;
    }

    /** Suma de todas las líneas de bono extra (€). */
    public function getExtraBonusSumFloat(): float
    {
        $sum = 0.0;
        foreach ($this->bonusLines as $line) {
            $sum += $line->getImporteFloat();
        }

        return round($sum, 2);
    }

    /**
     * @return list<array{eur: float, concepto: string}>
     */
    public function getExtraBonusLinesPayload(): array
    {
        $out = [];
        foreach ($this->bonusLines as $line) {
            $out[] = [
                'eur'      => $line->getImporteFloat(),
                'concepto' => $line->getConcepto() ?? '',
            ];
        }

        return $out;
    }

    public function getTotal(): string { return $this->total; }
    public function getTotalFloat(): float { return (float) $this->total; }
    public function setTotal(string $total): static { $this->total = $total; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }

    public function getNumero(): ?int { return $this->numero; }
    public function setNumero(?int $numero): static { $this->numero = $numero; return $this; }

    public function isBorrador(): bool { return $this->estado === 'borrador'; }
    public function isEnviada(): bool { return $this->estado === 'enviada'; }

    public function getEnviadaAt(): ?\DateTimeImmutable { return $this->enviadaAt; }
    public function setEnviadaAt(?\DateTimeImmutable $enviadaAt): static { $this->enviadaAt = $enviadaAt; return $this; }

    public function isPagada(): bool { return $this->pagadaAt !== null; }
    public function getPagadaAt(): ?\DateTimeImmutable { return $this->pagadaAt; }
    public function setPagadaAt(?\DateTimeImmutable $pagadaAt): static { $this->pagadaAt = $pagadaAt; return $this; }

    public function getComisionBancoHnl(): ?string { return $this->comisionBancoHnl; }
    public function getComisionBancoHnlFloat(): float { return (float) ($this->comisionBancoHnl ?? 0); }
    public function setComisionBancoHnl(?string $comisionBancoHnl): static { $this->comisionBancoHnl = $comisionBancoHnl; return $this; }

    /** Si hay valor guardado se usa; si no, el estimado fijo del cliente (L por cobro). */
    public function getEffectiveComisionBancoHnl(float $clienteRecargoHnlPorCobro): float
    {
        if ($this->comisionBancoHnl !== null && $this->comisionBancoHnl !== '') {
            return (float) $this->comisionBancoHnl;
        }

        return $clienteRecargoHnlPorCobro;
    }

    public function getReciboSwift(): ?int { return $this->reciboSwift; }
    public function setReciboSwift(?int $reciboSwift): static { $this->reciboSwift = $reciboSwift; return $this; }

    public function getTasaEmisionEurL(): ?string { return $this->tasaEmisionEurL; }
    public function getTasaEmisionEurLFloat(): float { return (float) ($this->tasaEmisionEurL ?? 0); }
    public function setTasaEmisionEurL(?string $v): static { $this->tasaEmisionEurL = $v; return $this; }

    public function getTasaEmisionUsdL(): ?string { return $this->tasaEmisionUsdL; }
    public function getTasaEmisionUsdLFloat(): float { return (float) ($this->tasaEmisionUsdL ?? 0); }
    public function setTasaEmisionUsdL(?string $v): static { $this->tasaEmisionUsdL = $v; return $this; }

    public function getTasaEmisionFecha(): ?string { return $this->tasaEmisionFecha; }
    public function setTasaEmisionFecha(?string $v): static { $this->tasaEmisionFecha = $v; return $this; }

    public function getTasaEmisionSource(): ?string { return $this->tasaEmisionSource; }
    public function setTasaEmisionSource(?string $v): static { $this->tasaEmisionSource = $v; return $this; }

    public function getTasaPagoEurL(): ?string { return $this->tasaPagoEurL; }
    public function getTasaPagoEurLFloat(): float { return (float) ($this->tasaPagoEurL ?? 0); }
    public function setTasaPagoEurL(?string $v): static { $this->tasaPagoEurL = $v; return $this; }

    public function getTasaPagoUsdL(): ?string { return $this->tasaPagoUsdL; }
    public function getTasaPagoUsdLFloat(): float { return (float) ($this->tasaPagoUsdL ?? 0); }
    public function setTasaPagoUsdL(?string $v): static { $this->tasaPagoUsdL = $v; return $this; }

    public function getTasaPagoFecha(): ?string { return $this->tasaPagoFecha; }
    public function setTasaPagoFecha(?string $v): static { $this->tasaPagoFecha = $v; return $this; }

    public function getTasaPagoSource(): ?string { return $this->tasaPagoSource; }
    public function setTasaPagoSource(?string $v): static { $this->tasaPagoSource = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getPaymentProofStoredFilename(): ?string { return $this->paymentProofStoredFilename; }
    public function setPaymentProofStoredFilename(?string $v): static { $this->paymentProofStoredFilename = $v; return $this; }

    public function getPaymentProofOriginalName(): ?string { return $this->paymentProofOriginalName; }
    public function setPaymentProofOriginalName(?string $v): static { $this->paymentProofOriginalName = $v; return $this; }

    public function getPaymentProofMime(): ?string { return $this->paymentProofMime; }
    public function setPaymentProofMime(?string $v): static { $this->paymentProofMime = $v; return $this; }

    public function hasPaymentProof(): bool
    {
        return $this->paymentProofStoredFilename !== null && $this->paymentProofStoredFilename !== '';
    }
}
