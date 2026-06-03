<?php

namespace App\Support\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;

class UserFormOptions
{
    /**
     * @return array{
     *     departments: list<array{id: string, name: string}>,
     *     roles: list<array{value: string, label: string}>
     * }
     */
    public static function for(User $actor): array
    {
        return [
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Department $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ])
                ->all(),
            'roles' => array_map(
                fn (UserRole $role) => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ],
                AssignableRoles::for($actor),
            ),
        ];
    }
}
