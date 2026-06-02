<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Policies\Concerns\AdminRoleAuthorization;

class DepartmentPolicy
{
    use AdminRoleAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewAdmin($user);
    }

    public function view(User $user, Department $department): bool
    {
        return $this->canViewAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->canManageOrganization($user);
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->canManageOrganization($user);
    }
}
