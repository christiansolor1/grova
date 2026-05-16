<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\MenuTreeBuilder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expone la ruta de keys en el árbol de menú (p. ej. resaltar antecesores del ítem activo en el sidebar).
 */
final class MenuPathExtension extends AbstractExtension
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu_path_keys', $this->getPathToMenuKey(...)),
            new TwigFunction('menu_path_nodes', $this->getMenuPathNodes(...)),
        ];
    }

    /**
     * @param list<array<string, mixed>> $menuTree
     *
     * @return list<array{key: string, label: string, href: string|null}>
     */
    public function getMenuPathNodes(array $menuTree, string $activeMenuKey): array
    {
        return $this->menuTreeBuilder->getMenuPathNodes($menuTree, $activeMenuKey);
    }

    /**
     * @param list<array<string, mixed>> $menuTree
     *
     * @return list<string>
     */
    public function getPathToMenuKey(array $menuTree, string $activeMenuKey): array
    {
        return $this->menuTreeBuilder->getPathToMenuKey($menuTree, $activeMenuKey);
    }
}
