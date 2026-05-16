<?php

namespace App\Twig;

use App\Service\BuildInfoProvider;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class BuildInfoExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly BuildInfoProvider $buildInfoProvider)
    {
    }

    public function getGlobals(): array
    {
        return [
            'build_info' => $this->buildInfoProvider->getBuildInfo(),
        ];
    }
}
