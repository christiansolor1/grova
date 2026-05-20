<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\LogActividadRepository::class)]
#[ORM\Table(name: 'log_actividad')]
#[ORM\Index(name: 'idx_log_usuario', columns: ['usuario_id'])]
#[ORM\Index(name: 'idx_log_accion', columns: ['accion'])]
#[ORM\Index(name: 'idx_log_created', columns: ['created_at'])]
class LogActividad
{
    public const ACCION_LOGIN_EXITOSO      = 'login_exitoso';
    public const ACCION_LOGIN_FALLIDO      = 'login_fallido';
    public const ACCION_2FA_FALLIDO        = '2fa_fallido';
    public const ACCION_2FA_EXITOSO        = '2fa_exitoso';
    public const ACCION_CAMBIO_CONTRASENA  = 'cambio_contrasena';
    public const ACCION_REGISTRO           = 'registro';
    public const ACCION_RECUPERACION       = 'recuperacion_solicitada';
    public const ACCION_GOOGLE_LOGIN       = 'google_login';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $usuario = null;

    #[ORM\Column(length: 80)]
    private string $accion = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 15)]
    private string $ip = '';

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $detalles = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sessionToken = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function crear(
        string $accion,
        string $ip,
        ?User $usuario = null,
        ?string $email = null,
        ?string $userAgent = null,
        ?array $detalles = null,
    ): self {
        $registro = new self();
        $registro->accion    = $accion;
        $registro->ip        = $ip;
        $registro->usuario   = $usuario;
        $registro->email     = $email;
        $registro->userAgent = $userAgent ? mb_substr($userAgent, 0, 512) : null;
        $registro->detalles  = $detalles;

        return $registro;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsuario(): ?User { return $this->usuario; }
    public function getAccion(): string { return $this->accion; }
    public function getEmail(): ?string { return $this->email; }
    public function getIp(): string { return $this->ip; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getDetalles(): ?array { return $this->detalles; }
    public function getSessionToken(): ?string { return $this->sessionToken; }
    public function setSessionToken(?string $token): static { $this->sessionToken = $token; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
