<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Entity;

use App\Module\Personal\Wallet\Repository\WalletTransferenciaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletTransferenciaRepository::class)]
#[ORM\Table(name: 'wallet_transferencia')]
class WalletTransferencia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monto = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $concepto = null;

    #[ORM\ManyToOne(targetEntity: WalletCuenta::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WalletCuenta $cuentaOrigen = null;

    #[ORM\ManyToOne(targetEntity: WalletCuenta::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WalletCuenta $cuentaDestino = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $comision = null;

    #[ORM\OneToOne(targetEntity: WalletEntry::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WalletEntry $entrySalida;

    #[ORM\OneToOne(targetEntity: WalletEntry::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WalletEntry $entryEntrada;

    #[ORM\OneToOne(targetEntity: WalletEntry::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WalletEntry $entryComision = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->fecha     = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static { $this->fecha = $fecha; return $this; }

    public function getMonto(): string { return $this->monto; }
    public function getMontoFloat(): float { return (float) $this->monto; }
    public function setMonto(string $monto): static { $this->monto = $monto; return $this; }

    public function getConcepto(): ?string { return $this->concepto; }
    public function setConcepto(?string $concepto): static { $this->concepto = $concepto; return $this; }

    public function getCuentaOrigen(): ?WalletCuenta { return $this->cuentaOrigen; }
    public function setCuentaOrigen(?WalletCuenta $cuenta): static { $this->cuentaOrigen = $cuenta; return $this; }

    public function getCuentaDestino(): ?WalletCuenta { return $this->cuentaDestino; }
    public function setCuentaDestino(?WalletCuenta $cuenta): static { $this->cuentaDestino = $cuenta; return $this; }

    public function getComision(): ?string { return $this->comision; }
    public function getComisionFloat(): float { return (float) ($this->comision ?? 0); }
    public function setComision(?string $comision): static { $this->comision = $comision; return $this; }

    public function getEntrySalida(): WalletEntry { return $this->entrySalida; }
    public function setEntrySalida(WalletEntry $entry): static { $this->entrySalida = $entry; return $this; }

    public function getEntryEntrada(): WalletEntry { return $this->entryEntrada; }
    public function setEntryEntrada(WalletEntry $entry): static { $this->entryEntrada = $entry; return $this; }

    public function getEntryComision(): ?WalletEntry { return $this->entryComision; }
    public function setEntryComision(?WalletEntry $entry): static { $this->entryComision = $entry; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getNombreOrigen(): string
    {
        return $this->cuentaOrigen?->getNombre() ?? 'Cartera general';
    }

    public function getNombreDestino(): string
    {
        return $this->cuentaDestino?->getNombre() ?? 'Cartera general';
    }
}
