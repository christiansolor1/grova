<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\PaseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaseRepository::class)]
#[ORM\Table(name: 'res_pase')]
#[ORM\Index(columns: ['qr_token'], name: 'idx_res_pase_token')]
#[ORM\Index(columns: ['tenant_id', 'estado'], name: 'idx_res_pase_tenant_estado')]
class Pase
{
    public const TIPOS = [
        'unica_vez'   => 'Única vez',
        'recurrente'  => 'Recurrente',
        'permanente'  => 'Permanente',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'usado'     => 'Usado',
        'vencido'   => 'Vencido',
        'cancelado' => 'Cancelado',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 36, unique: true)]
    private string $qrToken = '';

    #[ORM\Column(length: 20)]
    private string $tipo = 'unica_vez';

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaInicio = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaFin = null;

    /** Formato HH:MM */
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $horaInicio = null;

    /** Formato HH:MM */
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $horaFin = null;

    /** Array de días permitidos: 0=domingo … 6=sábado */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $diasSemana = null;

    /** Residente que genera la invitación */
    #[ORM\ManyToOne(targetEntity: Persona::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Persona $residente;

    /** Visitante específico — puede ser null para pases genéricos */
    #[ORM\ManyToOne(targetEntity: Visitante::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Visitante $visitante = null;

    #[ORM\ManyToOne(targetEntity: Casa::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Casa $casaDestino;

    #[ORM\Column(length: 20)]
    private string $estado = 'pendiente';

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
        $this->qrToken  = $this->generarToken();
    }

    private function generarToken(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getQrToken(): string { return $this->qrToken; }
    public function setQrToken(string $qrToken): static { $this->qrToken = $qrToken; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
    public function getTipoLabel(): string { return self::TIPOS[$this->tipo] ?? $this->tipo; }

    public function getFechaInicio(): ?\DateTimeImmutable { return $this->fechaInicio; }
    public function setFechaInicio(?\DateTimeImmutable $fechaInicio): static { $this->fechaInicio = $fechaInicio; return $this; }

    public function getFechaFin(): ?\DateTimeImmutable { return $this->fechaFin; }
    public function setFechaFin(?\DateTimeImmutable $fechaFin): static { $this->fechaFin = $fechaFin; return $this; }

    public function getHoraInicio(): ?string { return $this->horaInicio; }
    public function setHoraInicio(?string $horaInicio): static { $this->horaInicio = $horaInicio; return $this; }

    public function getHoraFin(): ?string { return $this->horaFin; }
    public function setHoraFin(?string $horaFin): static { $this->horaFin = $horaFin; return $this; }

    public function getDiasSemana(): ?array { return $this->diasSemana; }
    public function setDiasSemana(?array $diasSemana): static { $this->diasSemana = $diasSemana; return $this; }

    public function getResidente(): Persona { return $this->residente; }
    public function setResidente(Persona $residente): static { $this->residente = $residente; return $this; }

    public function getVisitante(): ?Visitante { return $this->visitante; }
    public function setVisitante(?Visitante $visitante): static { $this->visitante = $visitante; return $this; }

    public function getCasaDestino(): Casa { return $this->casaDestino; }
    public function setCasaDestino(Casa $casaDestino): static { $this->casaDestino = $casaDestino; return $this; }

    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }
    public function getEstadoLabel(): string { return self::ESTADOS[$this->estado] ?? $this->estado; }
    public function isActivo(): bool { return $this->estado === 'pendiente'; }

    public function getCreadoEn(): \DateTimeImmutable { return $this->creadoEn; }
}
