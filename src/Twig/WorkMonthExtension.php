<?php

declare(strict_types=1);

namespace App\Twig;

use App\Module\Personal\Work\WorkMonthLabel;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class WorkMonthExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('work_month_year', [$this, 'formatMonthYear']),
        ];
    }

    public function formatMonthYear(int $year, int $month, ?string $locale = null): string
    {
        $locale ??= $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es';

        return WorkMonthLabel::format($year, $month, $locale);
    }
}
