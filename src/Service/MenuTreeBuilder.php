<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use App\Workspace\WorkspaceLeafRoutes;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Construye el árbol de menú de la aplicación (etiquetas, iconos, enlaces) a partir
 * de los registros en {@see Menu}, aplicando visibilidad por rol e ítems solo-dev (p. ej. con ROLE_DEVELOPER).
 */
final class MenuTreeBuilder
{
    /** Clave de menú que corresponde a la ruta “inicio” del panel (sin segmento de sección). */
    public const HOME_MENU_KEY = 'dashboard';

    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return list<array{id: string, label: string, icon: string, href: string|null, children: list<array<string, mixed>>}>
     */
    /**
     * @param bool $includeDevOnlyItems p. ej. true si el usuario tiene ROLE_DEVELOPER (ítems con {@see Menu::isDevOnly}).
     */
    public function buildTree(bool $includeDevOnlyItems): array
    {
        $user  = $this->security->getUser();
        $menus = $this->filterVisible($this->menuRepository->findEnabledOrdered(), $user, $includeDevOnlyItems);
        $byKey = [];
        foreach ($menus as $menu) {
            $byKey[$menu->getMenuKey()] = $menu;
        }
        $byKey = $this->dropOrphans($byKey);

        $childrenMap = [];
        foreach ($byKey as $menu) {
            $parentKey = $menu->getParentKey();
            if ($parentKey === null) {
                continue;
            }
            if (!isset($byKey[$parentKey])) {
                continue;
            }
            $childrenMap[$parentKey][] = $menu;
        }
        foreach ($childrenMap as &$group) {
            usort($group, static fn (Menu $a, Menu $b) => $a->getSortOrder() <=> $b->getSortOrder());
        }
        unset($group);

        $roots = [];
        foreach ($byKey as $menu) {
            if ($menu->getParentKey() === null) {
                $roots[] = $menu;
            }
        }
        usort($roots, static fn (Menu $a, Menu $b) => $a->getSortOrder() <=> $b->getSortOrder());

        return array_map(fn (Menu $root) => $this->toNode($root, $childrenMap, $includeDevOnlyItems), $roots);
    }

    /**
     * @param list<array<string, mixed>> $tree
     *
     * @return list<string>
     */
    public function collectKeys(array $tree): array
    {
        $keys = [];
        foreach ($tree as $node) {
            $keys[] = $node['id'];
            $keys   = array_merge($keys, $this->collectKeys($node['children'] ?? []));
        }

        return $keys;
    }

    /**
     * @param Menu[] $menus
     *
     * @return Menu[]
     */
    private function filterVisible(array $menus, ?UserInterface $user, bool $includeDevOnlyItems): array
    {
        $visible = [];
        foreach ($menus as $menu) {
            if ($menu->isDevOnly() && !$includeDevOnlyItems) {
                continue;
            }
            $required = $menu->getRequiredRole();
            if ($required !== null && ($user === null || !$this->security->isGranted($required))) {
                continue;
            }
            $visible[] = $menu;
        }

        return $visible;
    }

    /**
     * @param array<string, Menu> $byKey
     *
     * @return array<string, Menu>
     */
    private function dropOrphans(array $byKey): array
    {
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($byKey as $key => $menu) {
                $parentKey = $menu->getParentKey();
                if ($parentKey !== null && !isset($byKey[$parentKey])) {
                    unset($byKey[$key]);
                    $changed = true;
                }
            }
        }

        return $byKey;
    }

    /**
     * @param array<string, list<Menu>> $childrenMap
     *
     * @return array{id: string, label: string, icon: string, href: string|null, children: list<array<string, mixed>>}
     */
    private function toNode(Menu $menu, array $childrenMap, bool $includeDevOnlyItems): array
    {
        $key         = $menu->getMenuKey();
        $rawChildren = $childrenMap[$key] ?? [];
        $childNodes  = array_map(fn (Menu $child) => $this->toNode($child, $childrenMap, $includeDevOnlyItems), $rawChildren);
        $hasChildren = $childNodes !== [];

        return [
            'id'       => $key,
            'label'    => $menu->getLabel(),
            'icon'     => $menu->getIcon(),
            'href'     => $hasChildren ? null : $this->hrefForLeafKey($key),
            'children' => $childNodes,
        ];
    }

    private function hrefForLeafKey(string $menuKey): string
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es';
        $params  = ['_locale' => $locale];

        $route = WorkspaceLeafRoutes::routeNameForMenuKey($menuKey);

        return $this->urlGenerator->generate($route, $params);
    }
}
