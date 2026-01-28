<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Plan::class => \App\Policies\PlanPolicy::class,
        \App\Models\Router::class => \App\Policies\RouterPolicy::class,
        \App\Models\Zone::class => \App\Policies\ZonePolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });
    }
}
