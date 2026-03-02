<?php

namespace App\Enums;

enum InternalProjectType: string
{
    case OFFICE = 'Office';
    case MACHINE = 'Machine';
    case TESTING = 'Testing';
    case FACILITIES = 'Facilities';
    case STORE = 'Store';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::OFFICE => 'bg-primary',
            self::MACHINE => 'bg-info',
            self::TESTING => 'bg-warning',
            self::FACILITIES => 'bg-success',
            self::STORE => 'bg-secondary',
        };
    }

    public function badgeIcon(): string
    {
        return match($this) {
            self::OFFICE => 'fa-building',
            self::MACHINE => 'fa-cogs',
            self::TESTING => 'fa-flask',
            self::FACILITIES => 'fa-tools',
            self::STORE => 'fa-store',
        };
    }
}