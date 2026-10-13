<?php

declare(strict_types=1);

namespace App\Module\Personal\Agenda\Entity;

use App\Module\Personal\Agenda\Repository\AgendaEventoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgendaEventoRepository::class)]
#[ORM\Table(name: 'agenda_evento')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_agenda_tenant')]
#[ORM\Index(columns: ['fecha'], name: 'idx_agenda_fecha')]
class AgendaEvento
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 200)]
    private string $nombre;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(length: 30, options: ['default' => 'evento'])]
    private string $tipo = 'evento';

    #[ORM\Column(options: ['default' => false])]
    private bool $recurrente = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $apodo = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $parentesco = null;

    /** HH:MM — solo para tipo evento/recordatorio */
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $hora = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $lugar = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $notas = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $notificarCorreo = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $notificarPush = false;

    /** @var list<int> días de anticipación para notificar: 0=mismo día, 1, 3, 7, 30 */
    #[ORM\Column(type: 'json')]
    private array $avisos = [1];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoAt;

    public function __construct()
    {
        $this->fecha    = new \DateTimeImmutable();
        $this->creadoAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function setFecha(\DateTimeImmutable $fecha): static { $this->fecha = $fecha; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }

    public function isRecurrente(): bool { return $this->recurrente; }
    public function setRecurrente(bool $recurrente): static { $this->recurrente = $recurrente; return $this; }

    public function getApodo(): ?string { return $this->apodo; }
    public function setApodo(?string $apodo): static { $this->apodo = $apodo !== '' ? $apodo : null; return $this; }

    public function getParentesco(): ?string { return $this->parentesco; }
    public function setParentesco(?string $parentesco): static { $this->parentesco = $parentesco !== '' ? $parentesco : null; return $this; }

    public function getNombreVisible(): string { return $this->apodo ?? $this->nombre; }

    public function getHora(): ?string { return $this->hora; }
    public function setHora(?string $hora): static { $this->hora = ($hora !== '' && $hora !== null) ? $hora : null; return $this; }

    public function getLugar(): ?string { return $this->lugar; }
    public function setLugar(?string $lugar): static { $this->lugar = $lugar !== '' ? $lugar : null; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url !== '' ? $url : null; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas !== '' ? $notas : null; return $this; }

    public function getColor(): ?string { return $this->color; }
    public function setColor(?string $color): static { $this->color = $color; return $this; }

    public function isNotificarCorreo(): bool { return $this->notificarCorreo; }
    public function setNotificarCorreo(bool $notificarCorreo): static { $this->notificarCorreo = $notificarCorreo; return $this; }

    public function isNotificarPush(): bool { return $this->notificarPush; }
    public function setNotificarPush(bool $notificarPush): static { $this->notificarPush = $notificarPush; return $this; }

    /** @return list<int> */
    public function getAvisos(): array { return $this->avisos; }

    /**
     * @param list<int> $avisos valores permitidos: 0, 1, 3, 7, 14, 30. -1 = ninguno (se ignora).
     */
    public function setAvisos(array $avisos): static
    {
        $permitidos = [0, 1, 3, 7, 14, 30];
        $validos    = array_values(array_unique(array_filter($avisos, fn($v) => in_array($v, $permitidos, true))));
        $this->avisos = $validos;
        return $this;
    }

    public function tieneAviso(int $dias): bool { return in_array($dias, $this->avisos, true); }

    public function getCreadoAt(): \DateTimeImmutable { return $this->creadoAt; }

    /**
     * Calcula cuántos días faltan para el evento (o su próximo aniversario si es recurrente).
     * Devuelve 0 si es hoy, un número positivo si es futuro, negativo si ya pasó (no recurrente).
     */
    public function diasRestantes(): int
    {
        $hoy = new \DateTimeImmutable('today');

        if (!$this->recurrente) {
            $fechaEvento = \DateTimeImmutable::createFromFormat('Y-m-d', $this->fecha->format('Y-m-d'));
            assert($fechaEvento !== false);
            return (int) $hoy->diff($fechaEvento)->days * ($fechaEvento >= $hoy ? 1 : -1);
        }

        $anioActual = (int) $hoy->format('Y');
        $mesEvento  = (int) $this->fecha->format('m');
        $diaEvento  = (int) $this->fecha->format('d');

        $candidato = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $anioActual, $mesEvento, $diaEvento));
        if ($candidato === false) {
            $candidato = new \DateTimeImmutable(sprintf('%04d-%02d-28', $anioActual, $mesEvento));
        }

        if ($candidato < $hoy) {
            $candidato = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $anioActual + 1, $mesEvento, $diaEvento));
            if ($candidato === false) {
                $candidato = new \DateTimeImmutable(sprintf('%04d-%02d-28', $anioActual + 1, $mesEvento));
            }
        }

        return (int) $hoy->diff($candidato)->days;
    }
}
