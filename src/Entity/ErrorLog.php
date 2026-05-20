<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ErrorLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ErrorLogRepository::class)]
#[ORM\Table(name: 'error_log')]
#[ORM\Index(columns: ['level'], name: 'idx_error_log_level')]
#[ORM\Index(columns: ['status'], name: 'idx_error_log_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_error_log_created')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_error_log_tenant')]
class ErrorLog
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_IGNORED = 'ignored';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $level = '';

    #[ORM\Column(length: 50)]
    private string $channel = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $extra = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $trace = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(nullable: true)]
    private ?int $line = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exceptionClass = null;

    #[ORM\Column(nullable: true)]
    private ?int $tenantId = null;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 20, options: ['default' => 'new'])]
    private string $status = self::STATUS_NEW;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters / Setters ──

    public function getId(): ?int { return $this->id; }

    public function getLevel(): string { return $this->level; }
    public function setLevel(string $level): static { $this->level = $level; return $this; }

    public function getChannel(): string { return $this->channel; }
    public function setChannel(string $channel): static { $this->channel = $channel; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function getContext(): ?array { return $this->context; }
    public function setContext(?array $context): static { $this->context = $context; return $this; }

    public function getExtra(): ?array { return $this->extra; }
    public function setExtra(?array $extra): static { $this->extra = $extra; return $this; }

    public function getTrace(): ?string { return $this->trace; }
    public function setTrace(?string $trace): static { $this->trace = $trace; return $this; }

    public function getFile(): ?string { return $this->file; }
    public function setFile(?string $file): static { $this->file = $file; return $this; }

    public function getLine(): ?int { return $this->line; }
    public function setLine(?int $line): static { $this->line = $line; return $this; }

    public function getExceptionClass(): ?string { return $this->exceptionClass; }
    public function setExceptionClass(?string $exceptionClass): static { $this->exceptionClass = $exceptionClass; return $this; }

    public function getTenantId(): ?int { return $this->tenantId; }
    public function setTenantId(?int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): static { $this->userId = $userId; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getLevelLabel(): string
    {
        return match ($this->level) {
            'DEBUG' => 'Debug',
            'INFO' => 'Info',
            'NOTICE' => 'Notice',
            'WARNING' => 'Warning',
            'ERROR' => 'Error',
            'CRITICAL' => 'Critical',
            'ALERT' => 'Alert',
            'EMERGENCY' => 'Emergency',
            default => $this->level,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'Nuevo',
            self::STATUS_IN_PROGRESS => 'En progreso',
            self::STATUS_RESOLVED => 'Resuelto',
            self::STATUS_IGNORED => 'Ignorado',
            default => $this->status,
        };
    }

    /** @return string[] */
    public static function getStatuses(): array
    {
        return [self::STATUS_NEW, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_IGNORED];
    }
}
