<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinDocumentoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinDocumentoRepository::class)]
#[ORM\Table(name: 'fin_documento')]
#[ORM\Index(columns: ['tenant_id', 'entidad_tipo', 'entidad_id'], name: 'idx_fin_doc_entidad')]
class FinDocumento
{
    public const ENTIDAD_COMPROMISO = 'compromiso';
    public const ENTIDAD_INVERSION  = 'inversion';
    public const ENTIDAD_ACREEDOR   = 'acreedor';
    public const ENTIDAD_CUOTA      = 'cuota';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 20)]
    private string $entidadTipo;

    #[ORM\Column]
    private int $entidadId;

    #[ORM\Column(length: 255)]
    private string $nombreOriginal;

    #[ORM\Column(length: 100)]
    private string $nombreAlmacenado;

    #[ORM\Column(length: 100)]
    private string $tipoMime;

    #[ORM\Column]
    private int $tamanoBytes;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $subidoAt;

    public function __construct()
    {
        $this->subidoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getEntidadTipo(): string { return $this->entidadTipo; }
    public function setEntidadTipo(string $v): static { $this->entidadTipo = $v; return $this; }

    public function getEntidadId(): int { return $this->entidadId; }
    public function setEntidadId(int $v): static { $this->entidadId = $v; return $this; }

    public function getNombreOriginal(): string { return $this->nombreOriginal; }
    public function setNombreOriginal(string $v): static { $this->nombreOriginal = $v; return $this; }

    public function getNombreAlmacenado(): string { return $this->nombreAlmacenado; }
    public function setNombreAlmacenado(string $v): static { $this->nombreAlmacenado = $v; return $this; }

    public function getTipoMime(): string { return $this->tipoMime; }
    public function setTipoMime(string $v): static { $this->tipoMime = $v; return $this; }

    public function getTamanoBytes(): int { return $this->tamanoBytes; }
    public function setTamanoBytes(int $v): static { $this->tamanoBytes = $v; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $v): static { $this->descripcion = $v; return $this; }

    public function getSubidoAt(): \DateTimeImmutable { return $this->subidoAt; }

    public function esImagen(): bool
    {
        return str_starts_with($this->tipoMime, 'image/');
    }

    public function esPdf(): bool
    {
        return $this->tipoMime === 'application/pdf';
    }

    public function tamanoLegible(): string
    {
        if ($this->tamanoBytes < 1024) {
            return $this->tamanoBytes . ' B';
        }
        if ($this->tamanoBytes < 1024 * 1024) {
            return round($this->tamanoBytes / 1024, 1) . ' KB';
        }
        return round($this->tamanoBytes / (1024 * 1024), 1) . ' MB';
    }
}
