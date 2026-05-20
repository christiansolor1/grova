<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Entity;

use App\Module\Personal\Work\Repository\WorkDayRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkDayRepository::class)]
#[ORM\Table(name: 'work_day')]
#[ORM\Index(columns: ['fecha'], name: 'idx_work_day_fecha')]
class WorkDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\ManyToOne(targetEntity: WorkClient::class)]
    #[ORM\JoinColumn(nullable: false)]
    private WorkClient $client;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    /** Hora de entrada HH:MM — null si no trabajó ese día */
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $horaEntrada = null;

    #[ORM\Column]
    private bool $esFeriado = false;

    #[ORM\Column]
    private bool $esVacacion = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->fecha     = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getClient(): WorkClient { return $this->client; }
    public function setClient(WorkClient $client): static { $this->client = $client; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static { $this->fecha = $fecha; return $this; }

    public function getHoraEntrada(): ?string { return $this->horaEntrada; }
    public function setHoraEntrada(?string $horaEntrada): static { $this->horaEntrada = $horaEntrada; return $this; }

    public function isEsFeriado(): bool { return $this->esFeriado; }
    public function setEsFeriado(bool $esFeriado): static { $this->esFeriado = $esFeriado; return $this; }

    public function isEsVacacion(): bool { return $this->esVacacion; }
    public function setEsVacacion(bool $esVacacion): static { $this->esVacacion = $esVacacion; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** Devuelve true si la hora de entrada es antes del límite del cliente */
    public function bonusAplica(): bool
    {
        if ($this->horaEntrada === null) {
            return false;
        }

        [$hE, $mE] = array_map('intval', explode(':', $this->horaEntrada));
        [$hL, $mL] = array_map('intval', explode(':', $this->client->getHoraLimiteBonus()));

        return ($hE * 60 + $mE) < ($hL * 60 + $mL);
    }

    public function trabajado(): bool
    {
        return $this->horaEntrada !== null && !$this->esFeriado && !$this->esVacacion;
    }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }
}
