<?php

namespace App\Policies;

use App\Models\Jurisdiction;
use App\Models\User;

class JurisdictionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_jurisdictions');
    }

    public function view(User $user, Jurisdiction $jurisdiction): bool
    {
        return $user->can('manage_jurisdictions');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_jurisdictions');
    }

    public function update(User $user, Jurisdiction $jurisdiction): bool
    {
        return $user->can('manage_jurisdictions');
    }

    public function delete(User $user, Jurisdiction $jurisdiction): bool
    {
        return $user->can('manage_jurisdictions');
    }
}