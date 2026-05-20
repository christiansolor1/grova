<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserCredencialBiometricaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCredencialBiometricaRepository::class)]
#[ORM\Table(name: 'user_credencial_biometrica')]
class UserCredencialBiometrica
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** ID de credencial WebAuthn en base64 */
    #[ORM\Column(type: 'text')]
    private string $credentialId;

    /** Clave pública en formato PEM */
    #[ORM\Column(type: 'text')]
    private string $publicKey;

    /** Nombre amigable del dispositivo, p.ej. "MacBook Pro", "iPhone 15" */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nombreDispositivo = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $ultimoUsoEn = null;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getCredentialId(): string { return $this->credentialId; }
    public function setCredentialId(string $id): static { $this->credentialId = $id; return $this; }

    public function getPublicKey(): string { return $this->publicKey; }
    public function setPublicKey(string $key): static { $this->publicKey = $key; return $this; }

    public function getNombreDispositivo(): ?string { return $this->nombreDispositivo; }
    public function setNombreDispositivo(?string $nombre): static { $this->nombreDispositivo = $nombre; return $this; }

    public function getCreadoEn(): \DateTimeImmutable { return $this->creadoEn; }

    public function getUltimoUsoEn(): ?\DateTimeImmutable { return $this->ultimoUsoEn; }
    public function setUltimoUsoEn(\DateTimeImmutable $ts): static { $this->ultimoUsoEn = $ts; return $this; }

    public function marcarUso(): void { $this->ultimoUsoEn = new \DateTimeImmutable(); }
}
