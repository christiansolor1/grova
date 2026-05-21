<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TenantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenant')]
class Tenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(length: 60, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 80)]
    private string $dbName = '';

    #[ORM\Column(length: 20)]
    private string $estado = 'activo';

    /**
     * staff | trial | cortesia | pago
     * staff = Super Admin / developers (sin restricción)
     * trial = periodo de prueba (30 días)
     * cortesia = feedback / early adopters
     * pago = clientes que pagan
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tipo = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDbName(): string { return $this->dbName; }
    public function setDbName(string $dbName): static { $this->dbName = $dbName; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isActivo(): bool { return $this->estado === 'activo'; }

    public function getTipo(): ?string { return $this->tipo; }
    public function setTipo(?string $tipo): static { $this->tipo = $tipo; return $this; }

    public function esStaff(): bool { return $this->tipo === 'staff'; }
    public function esCortesia(): bool { return $this->tipo === 'cortesia'; }
    public function esTrial(): bool { return $this->tipo === 'trial'; }
    public function esPago(): bool { return $this->tipo === 'pago'; }

    /** Iniciales para el avatar (máx. 2 letras). */
    public function getInitials(): string
    {
        $words = explode(' ', $this->nombre);
        $letters = '';
        foreach ($words as $w) {
            if ($w !== '') {
                $letters .= mb_strtoupper(mb_substr($w, 0, 1));
            }
            if (mb_strlen($letters) >= 2) {
                break;
            }
        }

        return $letters ?: '??';
    }
}
