<?php

declare(strict_types=1);

namespace App\Module\Personal\Work\Entity;

use App\Module\Personal\Work\Repository\WorkVacationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkVacationRepository::class)]
#[ORM\Table(name: 'work_vacation')]
class WorkVacation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaInicio;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fechaFin;

    #[ORM\Column]
    private int $dias = 0;

    /** 1 = primer semestre (ene-jun), 2 = segundo semestre (jul-dic) */
    #[ORM\Column]
    private int $semestre = 1;

    #[ORM\Column]
    private int $anio;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->fechaInicio = new \DateTimeImmutable();
        $this->fechaFin    = new \DateTimeImmutable();
        $this->anio        = (int) (new \DateTimeImmutable())->format('Y');
        $this->createdAt   = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getFechaInicio(): \DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(\DateTimeImmutable $fechaInicio): static { $this->fechaInicio = $fechaInicio; return $this; }

    public function getFechaFin(): \DateTimeImmutable { return $this->fechaFin; }
    public function setFechaFin(\DateTimeImmutable $fechaFin): static { $this->fechaFin = $fechaFin; return $this; }

    public function getDias(): int { return $this->dias; }
    public function setDias(int $dias): static { $this->dias = $dias; return $this; }

    public function getSemestre(): int { return $this->semestre; }
    public function setSemestre(int $semestre): static { $this->semestre = $semestre; return $this; }

    public function getAnio(): int { return $this->anio; }
    public function setAnio(int $anio): static { $this->anio = $anio; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
