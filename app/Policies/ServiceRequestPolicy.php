<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_service_requests');
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        // Soporte, Ventas y Admin deberían poder verlas
        return $user->can('manage_service_requests');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_service_requests');
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('manage_service_requests');
    }

    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('manage_service_requests');
    }
}