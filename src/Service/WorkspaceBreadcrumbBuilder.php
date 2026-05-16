<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Estado del breadcrumb del workspace (migas + «subir un nivel»).
 * Reutilizable desde Twig ({@see \App\Twig\WorkspaceBreadcrumbExtension}) o inyectando este servicio en controladores / otros servicios.
 */
final class WorkspaceBreadcrumbBuilder
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $menuTree Árbol devuelto por {@see MenuTreeBuilder::buildTree()}
     *
     * @return array{
     *     pathNodes: list<array{key: string, label: string, href: string|null}>,
     *     onLeaf: bool,
     *     leafHref: string|null,
     *     homeHref: string,
     *     backHref: string|null,
     *     showBack: bool,
     * }
     */
    public function build(array $menuTree, string $activeMenuKey, string $homeRouteName = 'grova_page_dashboard'): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale  = $request?->getLocale() ?? 'es';

        $homeHref = $this->urlGenerator->generate($homeRouteName, ['_locale' => $locale]);

        $pathNodes = $this->menuTreeBuilder->getMenuPathNodes($menuTree, $activeMenuKey);
        $piN       = $this->normalizePath($request?->getPathInfo());

        $leafHref = $pathNodes !== [] ? ($pathNodes[\count($pathNodes) - 1]['href'] ?? null) : null;
        $leafN    = $this->normalizePath($leafHref);
        $onLeaf   = $leafHref !== null && $piN === $leafN;

        $backHref = $this->resolveBackHref($pathNodes, $onLeaf, $leafHref, $homeHref);

        $showBack = $backHref !== null && $this->normalizePath($backHref) !== $piN;

        return [
            'pathNodes' => $pathNodes,
            'onLeaf'    => $onLeaf,
            'leafHref'  => $leafHref,
            'homeHref'  => $homeHref,
            'backHref'  => $backHref,
            'showBack'  => $showBack,
        ];
    }

    /**
     * @param list<array{key: string, label: string, href: string|null}> $pathNodes
     */
    private function resolveBackHref(array $pathNodes, bool $onLeaf, ?string $leafHref, string $homeHref): ?string
    {
        if ($pathNodes === []) {
            return $homeHref;
        }

        if (!$onLeaf && $leafHref !== null) {
            return $leafHref;
        }

        if ($onLeaf && \count($pathNodes) >= 2) {
            $ancestorsClosestFirst = array_reverse(\array_slice($pathNodes, 0, -1), true);
            foreach ($ancestorsClosestFirst as $seg) {
                $href = $seg['href'] ?? null;
                if ($href !== null && $href !== '') {
                    return $href;
                }
            }

            return $homeHref;
        }

        if ($onLeaf) {
            return $homeHref;
        }

        return $homeHref;
    }

    private function normalizePath(?string $pathOrUrl): string
    {
        if ($pathOrUrl === null || $pathOrUrl === '') {
            return '';
        }

        if (str_contains($pathOrUrl, '://')) {
            $parsed = parse_url($pathOrUrl);
            $pathOrUrl = \is_array($parsed) ? ($parsed['path'] ?? '') : '';
        }

        return trim($pathOrUrl, '/');
    }
}
