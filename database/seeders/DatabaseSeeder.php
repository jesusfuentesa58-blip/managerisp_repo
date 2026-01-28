<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Ejecutar el seeder de Roles que ya tenemos
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Crear el usuario Administrador manualmente (Sin Faker)
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador Sistema',
                'password' => bcrypt('123456789'), // Cambia esto
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('super-admin');
    }
}
