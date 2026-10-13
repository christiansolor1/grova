<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Entity;

use App\Module\Personal\FinancialPlanning\Repository\FinAutoevaluacionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Autoevaluación mensual basada en las Leyes del Oro de Babilonia (leyes 3–5)
 * y en el cumplimiento de las reglas del plan activo.
 * Una por tenant por mes.
 */
#[ORM\Entity(repositoryClass: FinAutoevaluacionRepository::class)]
#[ORM\Table(name: 'fin_autoevaluacion')]
#[ORM\UniqueConstraint(name: 'uq_fin_autoevaluacion_tenant_mes', columns: ['tenant_id', 'anio', 'mes'])]
class FinAutoevaluacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(type: 'smallint')]
    private int $anio;

    #[ORM\Column(type: 'smallint')]
    private int $mes;

    /**
     * Respuestas a las preguntas de reflexión.
     * JSON: [{pregunta: string, respuesta: int (1-5), comentario?: string}]
     */
    #[ORM\Column(type: 'json')]
    private array $respuestas = [];

    /** Puntuación global 0–100 derivada de respuestas. */
    #[ORM\Column(type: 'smallint', options: ['default' => 0])]
    private int $puntuacion = 0;

    /** ¿Se cumplió el 10% de ahorro Babilonia? */
    #[ORM\Column(options: ['default' => false])]
    private bool $cumplioBabilonia = false;

    /** ¿Se cumplió la regla 50/30/20 Harvard (o la regla del plan)? */
    #[ORM\Column(options: ['default' => false])]
    private bool $cumplioRegla = false;

    /** Reflexión libre del mes. */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reflexion = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoAt;

    public function __construct()
    {
        $this->creadoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): static { $this->tenantId = $v; return $this; }

    public function getAnio(): int { return $this->anio; }
    public function setAnio(int $v): static { $this->anio = $v; return $this; }

    public function getMes(): int { return $this->mes; }
    public function setMes(int $v): static { $this->mes = $v; return $this; }

    public function getRespuestas(): array { return $this->respuestas; }
    public function setRespuestas(array $v): static { $this->respuestas = $v; return $this; }

    public function getPuntuacion(): int { return $this->puntuacion; }
    public function setPuntuacion(int $v): static { $this->puntuacion = max(0, min(100, $v)); return $this; }

    public function isCumplioBabilonia(): bool { return $this->cumplioBabilonia; }
    public function setCumplioBabilonia(bool $v): static { $this->cumplioBabilonia = $v; return $this; }

    public function isCumplioRegla(): bool { return $this->cumplioRegla; }
    public function setCumplioRegla(bool $v): static { $this->cumplioRegla = $v; return $this; }

    public function getReflexion(): ?string { return $this->reflexion; }
    public function setReflexion(?string $v): static { $this->reflexion = $v; return $this; }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }
}
