<?php

namespace App\Support\Admin;

use App\Enums\UserRole;
use App\Models\User;

class AssignableRoles
{
    /**
     * @return list<UserRole>
     */
    public static function for(User $actor): array
    {
        return match ($actor->role) {
            UserRole::SuperAdmin => UserRole::cases(),
            UserRole::EventManager => [UserRole::Employee],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function valuesFor(User $actor): array
    {
        return array_map(
            fn (UserRole $role): string => $role->value,
            self::for($actor),
        );
    }
}
