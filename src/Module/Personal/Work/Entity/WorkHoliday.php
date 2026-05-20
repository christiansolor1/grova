<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Entity;

use App\Module\Personal\Work\Repository\WorkHolidayRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkHolidayRepository::class)]
#[ORM\Table(name: 'work_holiday')]
#[ORM\UniqueConstraint(columns: ['tenant_id', 'fecha'], name: 'uq_work_holiday_tenant_fecha')]
#[ORM\Index(columns: ['anio'], name: 'idx_work_holiday_anio')]
class WorkHoliday
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(length: 100)]
    private string $nombre = '';

    #[ORM\Column(name: 'anio')]
    private int $anio;

    public function getId(): ?int { return $this->id; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static
    {
        $this->fecha = $fecha;
        $this->anio  = (int) $fecha->format('Y');
        return $this;
    }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getAnio(): int { return $this->anio; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
