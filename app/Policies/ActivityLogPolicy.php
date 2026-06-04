<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;
use App\Policies\Concerns\AdminRoleAuthorization;

class ActivityLogPolicy
{
    use AdminRoleAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewAdmin($user);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $this->canViewAdmin($user);
    }
}
