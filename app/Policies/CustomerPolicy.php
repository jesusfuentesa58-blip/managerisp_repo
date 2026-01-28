<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * ¿Puede ver el menú "Clientes" en la barra lateral?
     */
    public function viewAny(User $user): bool
    {
        // Si tiene el permiso, ve el menú. Si no, desaparece.
        return $user->can('manage_customers');
    }

    /**
     * ¿Puede ver la ficha de un cliente específico?
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->can('manage_customers');
    }

    /**
     * ¿Puede crear nuevos clientes?
     */
    public function create(User $user): bool
    {
        // Puedes crear un permiso específico 'create_customers' o usar el general
        return $user->can('manage_customers');
    }

    /**
     * ¿Puede editar clientes existentes?
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->can('manage_customers');
    }

    /**
     * ¿Puede eliminar clientes?
     */
    public function delete(User $user, Customer $customer): bool
    {
        // Aquí podrías usar un permiso más estricto
        return $user->can('delete_customers'); 
    }
}