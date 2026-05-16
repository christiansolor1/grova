<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserLockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserLockRepository::class)]
#[ORM\Table(name: 'user_lock')]
class UserLock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** Hash bcrypt del PIN */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pinHash = null;

    /** Secciones bloqueadas: ['work', 'wallet', ...] */
    #[ORM\Column(type: 'json')]
    private array $lockedSections = [];

    /** Minutos antes de volver a pedir autenticación */
    #[ORM\Column]
    private int $unlockTtlMinutes = 30;

    /** ID de la credencial WebAuthn (base64url) */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $webauthnCredentialId = null;

    /** Clave pública WebAuthn (PEM) */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $webauthnPublicKey = null;

    #[ORM\Column]
    private bool $webauthnEnabled = false;

    /** Solicitar huella/Face ID automáticamente al entrar a una sección bloqueada */
    #[ORM\Column]
    private bool $webauthnAutoPrompt = true;

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getPinHash(): ?string { return $this->pinHash; }
    public function setPinHash(?string $pinHash): static { $this->pinHash = $pinHash; return $this; }

    public function getLockedSections(): array { return $this->lockedSections; }
    public function setLockedSections(array $lockedSections): static { $this->lockedSections = $lockedSections; return $this; }

    public function getUnlockTtlMinutes(): int { return $this->unlockTtlMinutes; }
    public function setUnlockTtlMinutes(int $unlockTtlMinutes): static { $this->unlockTtlMinutes = $unlockTtlMinutes; return $this; }

    public function getWebauthnCredentialId(): ?string { return $this->webauthnCredentialId; }
    public function setWebauthnCredentialId(?string $id): static { $this->webauthnCredentialId = $id; return $this; }

    public function getWebauthnPublicKey(): ?string { return $this->webauthnPublicKey; }
    public function setWebauthnPublicKey(?string $key): static { $this->webauthnPublicKey = $key; return $this; }

    public function isWebauthnEnabled(): bool { return $this->webauthnEnabled; }
    public function setWebauthnEnabled(bool $webauthnEnabled): static { $this->webauthnEnabled = $webauthnEnabled; return $this; }

    public function isWebauthnAutoPrompt(): bool { return $this->webauthnAutoPrompt; }
    public function setWebauthnAutoPrompt(bool $v): static { $this->webauthnAutoPrompt = $v; return $this; }

    public function hasPin(): bool { return $this->pinHash !== null; }

    public function verifyPin(string $pin): bool
    {
        if ($this->pinHash === null) return false;
        return password_verify($pin, $this->pinHash);
    }
}
