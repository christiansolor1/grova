<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(columns: ['user_id', 'read_at'], name: 'idx_notification_user_read')]
#[ORM\Index(columns: ['user_id', 'dismissed_at'], name: 'idx_notification_user_dismissed')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tenantSlug = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $body;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $module = null;

    #[ORM\Column(length: 20, options: ['default' => 'info'])]
    private string $type = 'info';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dismissedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getTenantSlug(): ?string { return $this->tenantSlug; }
    public function setTenantSlug(?string $tenantSlug): static { $this->tenantSlug = $tenantSlug; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getBody(): string { return $this->body; }
    public function setBody(string $body): static { $this->body = $body; return $this; }

    public function getIcon(): ?string { return $this->icon; }
    public function setIcon(?string $icon): static { $this->icon = $icon; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }

    public function getModule(): ?string { return $this->module; }
    public function setModule(?string $module): static { $this->module = $module; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getTypeEnum(): NotificationType { return NotificationType::from($this->type); }

    public function getReadAt(): ?\DateTimeImmutable { return $this->readAt; }
    public function setReadAt(?\DateTimeImmutable $readAt): static { $this->readAt = $readAt; return $this; }

    public function getDismissedAt(): ?\DateTimeImmutable { return $this->dismissedAt; }
    public function setDismissedAt(?\DateTimeImmutable $dismissedAt): static { $this->dismissedAt = $dismissedAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getContext(): ?array { return $this->context; }
    public function setContext(?array $context): static { $this->context = $context; return $this; }

    public function isRead(): bool { return $this->readAt !== null; }
    public function isDismissed(): bool { return $this->dismissedAt !== null; }
}
