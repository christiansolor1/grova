<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\PaqueteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaqueteRepository::class)]
#[ORM\Table(name: 'res_paquete')]
#[ORM\Index(columns: ['tenant_id', 'estado'], name: 'idx_res_paquete_tenant_estado')]
class Paquete
{
    public const ESTADOS = [
        'recibido'        => 'Recibido',
        'pendiente_retiro'=> 'Pendiente de retiro',
        'entregado'       => 'Entregado',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: Persona::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Persona $residente;

    #[ORM\ManyToOne(targetEntity: Casa::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Casa $casa;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $empresaMensajeria = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foto = null;

    #[ORM\Column(length: 20)]
    private string $estado = 'recibido';

    #[ORM\Column]
    private \DateTimeImmutable $recibidoEn;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $entregadoEn = null;

    public function __construct()
    {
        $this->recibidoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getResidente(): Persona { return $this->residente; }
    public function setResidente(Persona $residente): static { $this->residente = $residente; return $this; }

    public function getCasa(): Casa { return $this->casa; }
    public function setCasa(Casa $casa): static { $this->casa = $casa; return $this; }

    public function getEmpresaMensajeria(): ?string { return $this->empresaMensajeria; }
    public function setEmpresaMensajeria(?string $empresaMensajeria): static { $this->empresaMensajeria = $empresaMensajeria; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getFoto(): ?string { return $this->foto; }
    public function setFoto(?string $foto): static { $this->foto = $foto; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
    public function getEstadoLabel(): string { return self::ESTADOS[$this->estado] ?? $this->estado; }
    public function isPendiente(): bool { return $this->estado !== 'entregado'; }

    public function getRecibidoEn(): \DateTimeImmutable { return $this->recibidoEn; }

    public function getEntregadoEn(): ?\DateTimeImmutable { return $this->entregadoEn; }
    public function setEntregadoEn(?\DateTimeImmutable $entregadoEn): static { $this->entregadoEn = $entregadoEn; return $this; }

    public function entregar(): void
    {
        $this->estado      = 'entregado';
        $this->entregadoEn = new \DateTimeImmutable();
    }
}
