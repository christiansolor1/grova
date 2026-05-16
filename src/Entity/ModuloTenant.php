<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ModuloTenantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuloTenantRepository::class)]
#[ORM\Table(name: 'modulo_tenant')]
#[ORM\UniqueConstraint(name: 'uq_modulo_tenant', columns: ['tenant_id', 'modulo_key'])]
class ModuloTenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\Column(length: 60)]
    private string $moduloKey = '';

    #[ORM\Column]
    private bool $activo = true;

    public function getId(): ?int { return $this->id; }

    public function getTenant(): Tenant { return $this->tenant; }
    public function setTenant(Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getModuloKey(): string { return $this->moduloKey; }
    public function setModuloKey(string $moduloKey): static { $this->moduloKey = $moduloKey; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $activo): static { $this->activo = $activo; return $this; }
}
