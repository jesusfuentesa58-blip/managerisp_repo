<?php

namespace App\Policies;

use App\Models\Router;
use App\Models\User;

class RouterPolicy
{
    public function viewAny(User $user): bool
    {        
        return $user->can('manage_routers');
    }

    public function view(User $user, Router $router): bool
    {
        return $user->can('manage_routers');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_routers');
    }

    public function update(User $user, Router $router): bool
    {
        return $user->can('manage_routers');
    }

    public function delete(User $user, Router $router): bool
    {
        return $user->can('manage_routers');
    }
}