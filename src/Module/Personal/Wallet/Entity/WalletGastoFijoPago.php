<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Entity;

use App\Module\Personal\Wallet\Repository\WalletGastoFijoPagoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletGastoFijoPagoRepository::class)]
#[ORM\Table(name: 'wallet_gasto_fijo_pago')]
class WalletGastoFijoPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WalletGastoFijo::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WalletGastoFijo $gastoFijo;

    #[ORM\Column]
    private int $anio;

    #[ORM\Column]
    private int $mes;

    /** Monto total pagado */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monto;

    /** Cantidad de unidades compradas (opcional) */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $cantidad = null;

    /** Unidad de medida (opcional, ej: botellones) */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unidad = null;

    /** Precio por unidad en este pago (puede diferir del precio base) */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $precioUnitario = null;

    /** Pago extra (fuera del presupuesto planeado) */
    #[ORM\Column]
    private bool $esExtra = false;

    /** ID del WalletEntry creado al registrar el pago */
    #[ORM\Column(nullable: true)]
    private ?int $walletEntryId = null;

    /** Fecha real del pago (el usuario puede ajustarla) */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column]
    private \DateTimeImmutable $pagadoAt;

    public function __construct()
    {
        $this->fecha    = new \DateTimeImmutable();
        $this->pagadoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getGastoFijo(): WalletGastoFijo { return $this->gastoFijo; }
    public function setGastoFijo(WalletGastoFijo $gastoFijo): static { $this->gastoFijo = $gastoFijo; return $this; }

    public function getAnio(): int { return $this->anio; }
    public function setAnio(int $anio): static { $this->anio = $anio; return $this; }

    public function getMes(): int { return $this->mes; }
    public function setMes(int $mes): static { $this->mes = $mes; return $this; }

    public function getMonto(): string { return $this->monto; }
    public function getMontoFloat(): float { return (float) $this->monto; }
    public function setMonto(string $monto): static { $this->monto = $monto; return $this; }

    public function getCantidad(): ?string { return $this->cantidad; }
    public function getCantidadFloat(): float { return (float) ($this->cantidad ?? 0); }
    public function setCantidad(?string $cantidad): static { $this->cantidad = $cantidad; return $this; }

    public function getUnidad(): ?string { return $this->unidad; }
    public function setUnidad(?string $unidad): static { $this->unidad = $unidad; return $this; }

    public function getPrecioUnitario(): ?string { return $this->precioUnitario; }
    public function getPrecioUnitarioFloat(): float { return (float) ($this->precioUnitario ?? 0); }
    public function setPrecioUnitario(?string $precioUnitario): static { $this->precioUnitario = $precioUnitario; return $this; }

    public function isEsExtra(): bool { return $this->esExtra; }
    public function setEsExtra(bool $esExtra): static { $this->esExtra = $esExtra; return $this; }

    public function getWalletEntryId(): ?int { return $this->walletEntryId; }
    public function setWalletEntryId(?int $id): static { $this->walletEntryId = $id; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static { $this->fecha = $fecha; return $this; }

    public function getPagadoAt(): \DateTimeImmutable { return $this->pagadoAt; }
    public function setPagadoAt(\DateTimeImmutable $pagadoAt): static { $this->pagadoAt = $pagadoAt; return $this; }
}
