<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait AdminRoleAuthorization
{
    protected function canViewAdmin(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    protected function canManageOrganization(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::EventManager], true);
    }
}
