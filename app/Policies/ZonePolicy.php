<?php

namespace App\Policies;

use App\Models\Zone;
use App\Models\User;

class ZonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_zones');
    }

    public function view(User $user, Zone $zone): bool
    {
        return $user->can('manage_zones');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_zones');
    }

    public function update(User $user, Zone $zone): bool
    {
        return $user->can('manage_zones');
    }

    public function delete(User $user, Zone $zone): bool
    {
        return $user->can('manage_zones');
    }
}