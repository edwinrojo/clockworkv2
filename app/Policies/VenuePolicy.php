<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;
use App\Policies\Concerns\AdminRoleAuthorization;

class VenuePolicy
{
    use AdminRoleAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewAdmin($user);
    }

    public function view(User $user, Venue $venue): bool
    {
        return $this->canViewAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    public function update(User $user, Venue $venue): bool
    {
        return $this->canManageOrganization($user);
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $this->canManageOrganization($user);
    }
}
