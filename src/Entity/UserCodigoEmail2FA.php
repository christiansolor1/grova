<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_codigo_2fa_email')]
class UserCodigoEmail2FA
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $usuario;

    #[ORM\Column(length: 6)]
    private string $codigo = '';

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $usado = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function generar(User $usuario, int $digitos = 6): self
    {
        $codigo = '';
        for ($i = 0; $i < $digitos; $i++) {
            $codigo .= (string) random_int(0, 9);
        }

        $entity = new self();
        $entity->usuario   = $usuario;
        $entity->codigo    = $codigo;
        $entity->expiresAt = new \DateTimeImmutable('+5 minutes');

        return $entity;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsuario(): User { return $this->usuario; }
    public function getCodigo(): string { return $this->codigo; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function isUsado(): bool { return $this->usado; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function marcarUsado(): void { $this->usado = true; }

    public function esValido(string $codigo): bool
    {
        if ($this->usado) {
            return false;
        }

        if ($this->expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        return hash_equals($this->codigo, $codigo);
    }
}
