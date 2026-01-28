<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// IMPORTA TUS MODELOS Y POLICIES
use App\Models\Customer;
use App\Policies\CustomerPolicy;
use App\Models\Plan;
use App\Policies\PlanPolicy;
use App\Models\Router;
use App\Policies\RouterPolicy;
use App\Models\Zone;
use App\Policies\ZonePolicy;
use App\Models\Jurisdiction;
use App\Policies\JurisdictionPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * El mapa de políticas de la aplicación.
     */
    protected $policies = [
        // Políticas anteriores
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Plan::class => \App\Policies\PlanPolicy::class,
        \App\Models\Router::class => \App\Policies\RouterPolicy::class,
        \App\Models\Zone::class => \App\Policies\ZonePolicy::class,
        \App\Models\Jurisdiction::class => \App\Policies\JurisdictionPolicy::class,

        // NUEVAS POLÍTICAS AGREGADAS
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\ServiceRequest::class => \App\Policies\ServiceRequestPolicy::class,
        \App\Models\Installation::class => \App\Policies\InstallationPolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        
        // Regla de Oro: El Super Admin puede hacer TODO sin preguntar
        Gate::before(function ($user) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });
    }
}