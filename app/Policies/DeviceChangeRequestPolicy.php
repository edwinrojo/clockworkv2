<?php

namespace App\Policies;

use App\Models\DeviceChangeRequest;
use App\Models\User;

class DeviceChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', User::class);
    }

    public function review(User $user, DeviceChangeRequest $deviceChangeRequest): bool
    {
        return $user->can('update', $deviceChangeRequest->user);
    }
}
