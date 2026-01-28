<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_plans'); // Asegúrate de haber creado este permiso
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->can('manage_plans');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_plans');
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->can('manage_plans');
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->can('manage_plans');
    }
}