<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Entity;

use App\Module\Personal\Work\Repository\WorkClientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkClientRepository::class)]
#[ORM\Table(name: 'work_client')]
class WorkClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    /** CIF/NIF o número de identificación fiscal del cliente */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $cifNif = null;

    /** Dirección fiscal completa del cliente */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $direccion = null;

    /** Emails separados por coma */
    #[ORM\Column(length: 255)]
    private string $emailsFactura = '';

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $salarioBase = '1100.00';

    /** Bonus por día si se entra antes de la hora límite */
    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $bonusDia = '12.50';

    /** Hora límite HH:MM — entrar antes de esta hora activa el bonus */
    #[ORM\Column(length: 5)]
    private string $horaLimiteBonus = '08:00';

    /** Nombre del banco para datos de pago en factura */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $bancNombre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $bancDireccion = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $bancSwift = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bancCuenta = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $bancTitular = null;

    /** Comisión bancaria mensual fija en Lempiras (cobro por convertir divisa) */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $recargoHnl = null;

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
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getCifNif(): ?string { return $this->cifNif; }
    public function setCifNif(?string $cifNif): static { $this->cifNif = $cifNif; return $this; }

    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $direccion): static { $this->direccion = $direccion; return $this; }

    public function getEmailsFactura(): string { return $this->emailsFactura; }
    public function setEmailsFactura(string $emailsFactura): static { $this->emailsFactura = $emailsFactura; return $this; }

    /** @return string[] */
    public function getEmailsFacturaArray(): array
    {
        return array_map('trim', explode(',', $this->emailsFactura));
    }

    public function getSalarioBase(): string { return $this->salarioBase; }
    public function getSalarioBaseFloat(): float { return (float) $this->salarioBase; }
    public function setSalarioBase(string $salarioBase): static { $this->salarioBase = $salarioBase; return $this; }

    public function getBonusDia(): string { return $this->bonusDia; }
    public function getBonusDiaFloat(): float { return (float) $this->bonusDia; }
    public function setBonusDia(string $bonusDia): static { $this->bonusDia = $bonusDia; return $this; }

    public function getHoraLimiteBonus(): string { return $this->horaLimiteBonus; }
    public function setHoraLimiteBonus(string $horaLimiteBonus): static { $this->horaLimiteBonus = $horaLimiteBonus; return $this; }

    public function getBancNombre(): ?string { return $this->bancNombre; }
    public function setBancNombre(?string $bancNombre): static { $this->bancNombre = $bancNombre; return $this; }

    public function getBancDireccion(): ?string { return $this->bancDireccion; }
    public function setBancDireccion(?string $bancDireccion): static { $this->bancDireccion = $bancDireccion; return $this; }

    public function getBancSwift(): ?string { return $this->bancSwift; }
    public function setBancSwift(?string $bancSwift): static { $this->bancSwift = $bancSwift; return $this; }

    public function getBancCuenta(): ?string { return $this->bancCuenta; }
    public function setBancCuenta(?string $bancCuenta): static { $this->bancCuenta = $bancCuenta; return $this; }

    public function getBancTitular(): ?string { return $this->bancTitular; }
    public function setBancTitular(?string $bancTitular): static { $this->bancTitular = $bancTitular; return $this; }

    public function getRecargoHnl(): ?string { return $this->recargoHnl; }
    public function getRecargoHnlFloat(): float { return (float) ($this->recargoHnl ?? 0); }
    public function setRecargoHnl(?string $recargoHnl): static { $this->recargoHnl = $recargoHnl; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $activo): static { $this->activo = $activo; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
