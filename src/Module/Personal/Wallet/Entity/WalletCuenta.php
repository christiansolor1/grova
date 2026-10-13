<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Entity;

use App\Module\Personal\Wallet\Repository\WalletCuentaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletCuentaRepository::class)]
#[ORM\Table(name: 'wallet_cuenta')]
class WalletCuenta
{
    /** debito | credito | ahorro | aportaciones | ahorro_retirable | efectivo */
    public const TIPOS = ['debito', 'credito', 'ahorro', 'aportaciones', 'ahorro_retirable', 'efectivo'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 100)]
    private string $nombre;

    #[ORM\ManyToOne(targetEntity: WalletInstitucion::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WalletInstitucion $institucion = null;

    /** Número de cuenta bancaria (encriptado AES-256) */
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $numeroCuenta = null;

    /** Últimos 4 dígitos de la tarjeta asociada (encriptado AES-256) */
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $tarjetaDigitos = null;

    #[ORM\Column(length: 20)]
    private string $tipo = 'debito';

    /** Saldo con el que arrancaste a registrar (para no tener que ingresar todo el historial) */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $saldoInicial = '0.00';

    #[ORM\Column(length: 7)]
    private string $color = '#64748b';

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getInstitucion(): ?WalletInstitucion { return $this->institucion; }
    public function setInstitucion(?WalletInstitucion $institucion): static { $this->institucion = $institucion; return $this; }
    public function getInstitucionNombre(): ?string { return $this->institucion?->getNombre(); }
    public function getInstitucionColor(): string { return $this->institucion?->getColor() ?? $this->color; }

    public function getNumeroCuenta(): ?string { return $this->numeroCuenta; }
    public function setNumeroCuenta(?string $v): static { $this->numeroCuenta = $v !== '' ? $v : null; return $this; }

    public function getTarjetaDigitos(): ?string { return $this->tarjetaDigitos; }
    public function setTarjetaDigitos(?string $v): static { $this->tarjetaDigitos = ($v !== null && $v !== '') ? $v : null; return $this; }

    /** Etiqueta para mostrar: "BAC Débito · Planilla" */
    public function getLabel(): string
    {
        $label = $this->nombre;
        if ($this->institucion) {
            $label = $this->institucion->getNombre() . ' · ' . $label;
        }
        return $label;
    }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }

    public function getSaldoInicial(): string { return $this->saldoInicial; }
    public function getSaldoInicialFloat(): float { return (float) $this->saldoInicial; }
    public function setSaldoInicial(string $saldoInicial): static { $this->saldoInicial = $saldoInicial; return $this; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $color): static { $this->color = $color; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $activo): static { $this->activo = $activo; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isCredito(): bool { return $this->tipo === 'credito'; }

    public function getTipoLabel(): string
    {
        return match ($this->tipo) {
            'debito'          => 'Débito',
            'credito'         => 'Crédito',
            'ahorro'          => 'Ahorro',
            'aportaciones'    => 'Aportaciones',
            'ahorro_retirable'=> 'Ahorro retirable',
            'efectivo'        => 'Efectivo',
            default           => $this->tipo,
        };
    }
}
