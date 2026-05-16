<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationType: string
{
    case Info    = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger  = 'danger';

    public function color(): string
    {
        return match($this) {
            self::Info    => '#60a5fa',
            self::Success => '#4ade80',
            self::Warning => '#fbbf24',
            self::Danger  => '#f87171',
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Info    => 'Info',
            self::Success => 'Éxito',
            self::Warning => 'Aviso',
            self::Danger  => 'Error',
        };
    }
}
