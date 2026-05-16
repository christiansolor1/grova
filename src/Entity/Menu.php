<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'menu')]
#[ORM\UniqueConstraint(name: 'uniq_menu_key', columns: ['menu_key'])]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $menuKey;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $parentKey = null;

    #[ORM\Column(length: 180)]
    private string $label;

    #[ORM\Column(length: 50)]
    private string $icon = 'list';

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $devOnly = false;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $requiredRole = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMenuKey(): string
    {
        return $this->menuKey;
    }

    public function setMenuKey(string $menuKey): self
    {
        $this->menuKey = $menuKey;

        return $this;
    }

    public function getParentKey(): ?string
    {
        return $this->parentKey;
    }

    public function setParentKey(?string $parentKey): self
    {
        $this->parentKey = $parentKey;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isDevOnly(): bool
    {
        return $this->devOnly;
    }

    public function setDevOnly(bool $devOnly): self
    {
        $this->devOnly = $devOnly;

        return $this;
    }

    public function getRequiredRole(): ?string
    {
        return $this->requiredRole;
    }

    public function setRequiredRole(?string $requiredRole): self
    {
        $this->requiredRole = $requiredRole;

        return $this;
    }
}
