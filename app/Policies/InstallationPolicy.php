<?php

namespace App\Policies;

use App\Models\Installation;
use App\Models\User;

class InstallationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'super-admin',
            'admin',
            'billing',
            'support'
        ]);
    }

    public function view(User $user, Installation $installation): bool
    {
        return $user->hasAnyRole([
            'super-admin',
            'admin',
            'billing',
            'support'
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function update(User $user, Installation $installation): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function delete(User $user, Installation $installation): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

}