<?php

declare(strict_types=1);

namespace App\Module\Residencial\Entity;

use App\Module\Residencial\Repository\RegistroEntradaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegistroEntradaRepository::class)]
#[ORM\Table(name: 'res_registro_entrada')]
#[ORM\Index(columns: ['tenant_id', 'hora_entrada'], name: 'idx_res_registro_tenant_hora')]
class RegistroEntrada
{
    public const TIPOS_ENTRADA = [
        'qr'     => 'QR',
        'manual' => 'Manual',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    /** Null en entradas manuales sin pase */
    #[ORM\ManyToOne(targetEntity: Pase::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Pase $pase = null;

    #[ORM\ManyToOne(targetEntity: Visitante::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Visitante $visitante;

    #[ORM\ManyToOne(targetEntity: Persona::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Persona $guardia;

    #[ORM\Column(length: 20)]
    private string $tipoEntrada = 'qr';

    #[ORM\Column]
    private \DateTimeImmutable $horaEntrada;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $horaSalida = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fotoEntrada = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;

    #[ORM\Column]
    private bool $esPrimeraVez = false;

    public function __construct()
    {
        $this->horaEntrada = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getPase(): ?Pase { return $this->pase; }
    public function setPase(?Pase $pase): static { $this->pase = $pase; return $this; }

    public function getVisitante(): Visitante { return $this->visitante; }
    public function setVisitante(Visitante $visitante): static { $this->visitante = $visitante; return $this; }

    public function getGuardia(): Persona { return $this->guardia; }
    public function setGuardia(Persona $guardia): static { $this->guardia = $guardia; return $this; }

    public function getTipoEntrada(): string { return $this->tipoEntrada; }
    public function setTipoEntrada(string $tipoEntrada): static { $this->tipoEntrada = $tipoEntrada; return $this; }
    public function getTipoEntradaLabel(): string { return self::TIPOS_ENTRADA[$this->tipoEntrada] ?? $this->tipoEntrada; }

    public function getHoraEntrada(): \DateTimeImmutable { return $this->horaEntrada; }
    public function setHoraEntrada(\DateTimeImmutable $horaEntrada): static { $this->horaEntrada = $horaEntrada; return $this; }

    public function getHoraSalida(): ?\DateTimeImmutable { return $this->horaSalida; }
    public function setHoraSalida(?\DateTimeImmutable $horaSalida): static { $this->horaSalida = $horaSalida; return $this; }

    public function getFotoEntrada(): ?string { return $this->fotoEntrada; }
    public function setFotoEntrada(?string $fotoEntrada): static { $this->fotoEntrada = $fotoEntrada; return $this; }

    public function getObservaciones(): ?string { return $this->observaciones; }
    public function setObservaciones(?string $observaciones): static { $this->observaciones = $observaciones; return $this; }

    public function isEsPrimeraVez(): bool { return $this->esPrimeraVez; }
    public function setEsPrimeraVez(bool $esPrimeraVez): static { $this->esPrimeraVez = $esPrimeraVez; return $this; }

    public function isActiva(): bool { return $this->horaSalida === null; }
}
