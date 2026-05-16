<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\WorkspaceBreadcrumbBuilder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expone {@see WorkspaceBreadcrumbBuilder} en plantillas: {@code workspace_breadcrumb(menu_tree, active_menu_key, home_route)}.
 */
final class WorkspaceBreadcrumbExtension extends AbstractExtension
{
    public function __construct(
        private readonly WorkspaceBreadcrumbBuilder $workspaceBreadcrumbBuilder,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('workspace_breadcrumb', [$this, 'workspaceBreadcrumb']),
        ];
    }

    /**
     * @param list<array<string, mixed>> $menuTree
     *
     * @return array<string, mixed>
     */
    public function workspaceBreadcrumb(array $menuTree, string $activeMenuKey = '', ?string $homeRouteName = null): array
    {
        return $this->workspaceBreadcrumbBuilder->build(
            $menuTree,
            $activeMenuKey,
            $homeRouteName ?? 'grova_page_dashboard',
        );
    }
}
