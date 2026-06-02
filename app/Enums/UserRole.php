<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case EventManager = 'event_manager';
    case Viewer = 'viewer';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::EventManager => 'Event Manager',
            self::Viewer => 'Viewer',
            self::Employee => 'Employee',
        };
    }

    public function canAccessAdmin(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::EventManager, self::Viewer => true,
            self::Employee => false,
        };
    }
}
