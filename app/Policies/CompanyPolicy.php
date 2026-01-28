<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * VER EL MENÚ: Super Admin y Admin
     */
    public function viewAny(User $user): bool
    {
        // Aquí agregamos 'admin' a la lista permitida
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * VER DETALLES: Super Admin y Admin
     */
    public function view(User $user, Company $company): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * CREAR: Solo Super Admin
     * (Normalmente solo existe 1 empresa, no deberían crear más)
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * EDITAR: Super Admin y Admin
     * (El admin necesita poder cambiar logo, dirección, etc.)
     */
    public function update(User $user, Company $company): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * ELIMINAR: SOLO Super Admin
     * (Borrar la empresa es peligroso)
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->hasRole('super-admin');
    }
}