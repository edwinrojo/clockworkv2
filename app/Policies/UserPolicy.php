<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\Concerns\AdminRoleAuthorization;

class UserPolicy
{
    use AdminRoleAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewAdmin($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->canViewAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $this->canManageUsers($user) && $this->canManageTarget($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $this->canManageUsers($user) && $this->canManageTarget($user, $model);
    }

    public function assignRole(User $user, UserRole $role): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::EventManager) {
            return $role === UserRole::Employee;
        }

        return false;
    }

    protected function canManageUsers(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::EventManager], true);
    }

    protected function canManageTarget(User $actor, User $target): bool
    {
        if ($actor->role === UserRole::SuperAdmin) {
            return true;
        }

        return $target->role === UserRole::Employee;
    }
}
