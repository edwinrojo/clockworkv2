<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Policies\Concerns\AdminRoleAuthorization;

class EventPolicy
{
    use AdminRoleAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewAdmin($user);
    }

    public function view(User $user, Event $event): bool
    {
        return $this->canViewAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    public function update(User $user, Event $event): bool
    {
        return $this->canManageOrganization($user);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->canManageOrganization($user);
    }

    public function manageSession(User $user, Event $event): bool
    {
        return $this->canManageOrganization($user);
    }

    public function viewAttendances(User $user, Event $event): bool
    {
        return $this->canViewAdmin($user);
    }

    public function manageAttendances(User $user, Event $event): bool
    {
        return $this->canManageOrganization($user);
    }
}
