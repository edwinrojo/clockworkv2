<?php

namespace App\Support\Inertia;

use App\Models\Department;
use App\Models\User;
use App\Models\Venue;

class AdminPermissions
{
    /**
     * @return array<string, array<string, bool>>
     */
    public static function for(?User $user): array
    {
        if ($user === null) {
            return [
                'departments' => self::emptyAbilities(),
                'venues' => self::emptyAbilities(),
            ];
        }

        return [
            'departments' => [
                'viewAny' => $user->can('viewAny', Department::class),
                'create' => $user->can('create', Department::class),
            ],
            'venues' => [
                'viewAny' => $user->can('viewAny', Venue::class),
                'create' => $user->can('create', Venue::class),
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function emptyAbilities(): array
    {
        return [
            'viewAny' => false,
            'create' => false,
        ];
    }
}
