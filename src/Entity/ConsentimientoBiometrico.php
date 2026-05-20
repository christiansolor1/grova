<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'consentimiento_biometrico')]
class ConsentimientoBiometrico
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $usuario;

    #[ORM\Column]
    private \DateTimeImmutable $acceptedAt;

    #[ORM\Column(length: 15)]
    private string $ip = '';

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $userAgent = null;

    /** Versión del formulario de consentimiento */
    #[ORM\Column(length: 10, options: ['default' => '1.0'])]
    private string $version = '1.0';

    public function __construct()
    {
        $this->acceptedAt = new \DateTimeImmutable();
    }

    public static function crear(User $usuario, string $ip, ?string $userAgent = null): self
    {
        $entity = new self();
        $entity->usuario   = $usuario;
        $entity->ip        = $ip;
        $entity->userAgent = $userAgent ? mb_substr($userAgent, 0, 512) : null;

        return $entity;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsuario(): User { return $this->usuario; }
    public function getAcceptedAt(): \DateTimeImmutable { return $this->acceptedAt; }
    public function getIp(): string { return $this->ip; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getVersion(): string { return $this->version; }

    public function haConsentido(User $usuario): bool
    {
        return $this->usuario->getId() === $usuario->getId();
    }
}
