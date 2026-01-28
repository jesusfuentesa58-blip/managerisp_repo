<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // =========================
        // Permisos del sistema
        // =========================
        $permissions = [
            // Gestión de Red
            'manage_jurisdictions', 'manage_routers', 'manage_zones', 'manage_plans',
            
            // Ventas y Clientes
            'manage_customers', 'manage_service_requests', // <--- Nuevos
            
            // Operaciones Técnicas
            'manage_services', 'manage_installations', // <--- Nuevos
            'suspend_services', 'reactivate_services', 'view_service_status',

            // Administración
            'manage_billing', 'view_payments', 'send_reminders',
            'manage_automations', 'run_automations',
            'view_logs', 'manage_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // =========================
        // Roles
        // =========================
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $admin      = Role::firstOrCreate(['name' => 'admin']);
        $support    = Role::firstOrCreate(['name' => 'support']);
        $billing    = Role::firstOrCreate(['name' => 'billing']);

        // =========================
        // Asignación de permisos
        // =========================

        // Super Admin -> todos
        $superAdmin->syncPermissions(Permission::all());

        // Administrador / Gerente
        $admin->syncPermissions([
            'manage_customers',
            'manage_jurisdictions',
            'manage_routers',
            'manage_zones',
            'manage_users',
            'manage_plans',
            'manage_installations',
            'manage_service_requests',
            'manage_automations',
            'run_automations',
            'manage_billing',
            'view_logs',
        ]);

        // Soporte Técnico
        $support->syncPermissions([
            'manage_customers',
            'manage_services',
            'suspend_services',
            'manage_installations',
            'reactivate_services',
            'view_service_status',
        ]);

        // Facturación
        $billing->syncPermissions([
            'manage_customers',
            'manage_service_requests',
            'manage_installations',           
            'manage_billing',
            'view_payments',
            'send_reminders',
            'view_logs',
        ]);
    }
}
