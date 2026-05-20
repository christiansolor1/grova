<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TotpTwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Tenant $tenant = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $apellido = null;

    #[ORM\Column]
    private bool $emailVerificado = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $tokenVerificacion = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $tokenVerificaExpira = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', (string) ($this->password ?? ''));

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(?string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getApellido(): ?string { return $this->apellido; }
    public function setApellido(?string $apellido): static { $this->apellido = $apellido; return $this; }

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpira = null;

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $token): static { $this->resetToken = $token; return $this; }

    public function getResetTokenExpira(): ?\DateTimeImmutable { return $this->resetTokenExpira; }
    public function setResetTokenExpira(?\DateTimeImmutable $expira): static { $this->resetTokenExpira = $expira; return $this; }

    public function isEmailVerificado(): bool { return $this->emailVerificado; }
    public function setEmailVerificado(bool $emailVerificado): static { $this->emailVerificado = $emailVerificado; return $this; }

    public function getTokenVerificacion(): ?string { return $this->tokenVerificacion; }
    public function setTokenVerificacion(?string $token): static { $this->tokenVerificacion = $token; return $this; }

    public function getTokenVerificaExpira(): ?\DateTimeImmutable { return $this->tokenVerificaExpira; }
    public function setTokenVerificaExpira(?\DateTimeImmutable $expira): static { $this->tokenVerificaExpira = $expira; return $this; }

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column]
    private bool $webauthn2faEnabled = true;

    #[ORM\Column]
    private bool $pin2faEnabled = true;

    #[ORM\Column]
    private bool $totp2faEnabled = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $email2faEnabled = false;

    public function getTotpSecret(): ?string { return $this->totpSecret; }
    public function setTotpSecret(?string $secret): static { $this->totpSecret = $secret; return $this; }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpSecret !== null;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->email ?? '';
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if ($this->totpSecret === null) {
            return null;
        }

        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function isWebauthn2faEnabled(): bool { return $this->webauthn2faEnabled; }
    public function setWebauthn2faEnabled(bool $v): static { $this->webauthn2faEnabled = $v; return $this; }

    public function isPin2faEnabled(): bool { return $this->pin2faEnabled; }
    public function setPin2faEnabled(bool $v): static { $this->pin2faEnabled = $v; return $this; }

    public function isTotp2faEnabled(): bool { return $this->totp2faEnabled; }
    public function setTotp2faEnabled(bool $v): static { $this->totp2faEnabled = $v; return $this; }

    public function isEmail2faEnabled(): bool { return $this->email2faEnabled; }
    public function setEmail2faEnabled(bool $v): static { $this->email2faEnabled = $v; return $this; }

    public function getNombreCompleto(): string
    {
        return trim(($this->nombre ?? '') . ' ' . ($this->apellido ?? ''));
    }

    /** Iniciales para el avatar. */
    public function getInitials(): string
    {
        $n = $this->nombre ? mb_strtoupper(mb_substr($this->nombre, 0, 1)) : '';
        $a = $this->apellido ? mb_strtoupper(mb_substr($this->apellido, 0, 1)) : '';

        return ($n . $a) ?: mb_strtoupper(mb_substr($this->email ?? '?', 0, 2));
    }
}
