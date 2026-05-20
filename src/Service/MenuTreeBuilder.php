<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use App\Service\TenantContext;
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

    /**
     * Claves de menú que corresponden a módulos — se muestran solo si el tenant los tiene activos.
     * Las claves que NO están aquí (dashboard, users-*, ui-*, etc.) siempre se muestran.
     */
    private const MODULE_KEYS = [
        'wallet', 'work', 'agenda', 'habitos', 'pesca',
        'legal', 'contactos', 'construccion',
        'pos', 'restaurante', 'clinica', 'financiera',
        'inventario', 'rrhh', 'facturacion',
    ];

    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * @return list<array{id: string, label: string, icon: string, status: string, href: string|null, uiBadge: string|null, uiStyle: array<string, string>|null, children: list<array<string, mixed>>}>
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
     * Devuelve la cadena de keys desde la raíz hasta $targetKey (inclusive), o [] si no existe.
     * Sirve para resaltar en el sidebar el camino a la vista actual.
     *
     * @param list<array<string, mixed>> $tree
     *
     * @return list<string>
     */
    public function getPathToMenuKey(array $tree, string $targetKey): array
    {
        $path = [];
        if ($this->pathContainsKey($tree, $targetKey, $path)) {
            return $path;
        }

        return [];
    }

    /**
     * Nodos del menú desde la raíz hasta la clave activa (label + href del árbol ya resuelto).
     *
     * @param list<array<string, mixed>> $tree
     *
     * @return list<array{key: string, label: string, href: string|null}>
     */
    public function getMenuPathNodes(array $tree, string $activeMenuKey): array
    {
        if ($activeMenuKey === '') {
            return [];
        }

        $keys = $this->getPathToMenuKey($tree, $activeMenuKey);
        if ($keys === []) {
            return [];
        }

        $nodes = [];
        foreach ($keys as $key) {
            $node = $this->findNodeById($tree, $key);
            if ($node === null) {
                continue;
            }
            $nodes[] = [
                'key'   => $key,
                'label' => (string) ($node['label'] ?? $key),
                'href'  => $node['href'] ?? null,
            ];
        }

        return $nodes;
    }

    /**
     * @param list<array<string, mixed>> $tree
     *
     * @return array<string, mixed>|null
     */
    private function findNodeById(array $tree, string $id): ?array
    {
        foreach ($tree as $node) {
            if (($node['id'] ?? '') === $id) {
                return $node;
            }
            $found = $this->findNodeById($node['children'] ?? [], $id);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $tree
     * @param list<string>             $path
     */
    private function pathContainsKey(array $tree, string $targetKey, array &$path): bool
    {
        foreach ($tree as $node) {
            $path[] = $node['id'];
            if ($node['id'] === $targetKey) {
                return true;
            }
            $children = $node['children'] ?? [];
            if ($children !== [] && $this->pathContainsKey($children, $targetKey, $path)) {
                return true;
            }
            array_pop($path);
        }

        return false;
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
        $modulos        = $this->tenantContext->getModulosActivos();
        $hasTenant      = $this->tenantContext->hasTenant();
        $filterModules  = $hasTenant && $modulos !== [];

        $visible = [];
        foreach ($menus as $menu) {
            if ($menu->isDevOnly() && !$includeDevOnlyItems) {
                continue;
            }
            $required = $menu->getRequiredRole();
            if ($required !== null && ($user === null || !$this->security->isGranted($required))) {
                continue;
            }
            // Si el ítem corresponde a un módulo, solo mostrarlo si el tenant lo tiene activo
            if ($filterModules && \in_array($menu->getMenuKey(), self::MODULE_KEYS, true)) {
                if (!\in_array($menu->getMenuKey(), $modulos, true)) {
                    continue;
                }
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
     * @return array{id: string, label: string, icon: string, status: string, href: string|null, uiBadge: string|null, uiStyle: array<string, string>|null, children: list<array<string, mixed>>}
     */
    private function toNode(Menu $menu, array $childrenMap, bool $includeDevOnlyItems): array
    {
        $key         = $menu->getMenuKey();
        $rawChildren = $childrenMap[$key] ?? [];
        $childNodes  = array_map(fn (Menu $child) => $this->toNode($child, $childrenMap, $includeDevOnlyItems), $rawChildren);
        $hasChildren = $childNodes !== [];

        $isPending = strtolower($menu->getStatus()) === 'pendiente';

        $uiStyleRaw = $menu->getUiStyle();
        $uiStyle = null;
        if (\is_string($uiStyleRaw) && trim($uiStyleRaw) !== '') {
            $decoded = json_decode($uiStyleRaw, true);
            if (\is_array($decoded)) {
                $uiStyle = $decoded;
            }
        }

        return [
            'id'       => $key,
            'label'    => $menu->getLabel(),
            'icon'     => $menu->getIcon(),
            'status'   => $menu->getStatus(),
            'uiBadge'  => $menu->getUiBadge(),
            'uiStyle'  => $uiStyle,
            'href'     => ($hasChildren || $isPending) ? null : $this->hrefForLeafKey($key),
            'children' => $childNodes,
        ];
    }

    private function hrefForLeafKey(string $menuKey): ?string
    {
        try {
            $route = WorkspaceLeafRoutes::routeNameForMenuKey($menuKey);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es';

        // Si el locale actual no funciona (ej. rutas que solo aceptan es), se cae
        // a es como fallback.
        $locales = $locale !== 'es' ? [$locale, 'es'] : ['es'];
        foreach ($locales as $tryLocale) {
            try {
                return $this->urlGenerator->generate($route, ['_locale' => $tryLocale]);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        return null;
    }
}
