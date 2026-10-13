<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\EncryptionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class DecryptExtension extends AbstractExtension
{
    public function __construct(private readonly EncryptionService $encryption)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('decrypt', [$this, 'decrypt']),
        ];
    }

    public function decrypt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return $this->encryption->decrypt($value);
        } catch (\Throwable) {
            // Valor no encriptado (datos anteriores a la encriptación)
            return $value;
        }
    }
}
