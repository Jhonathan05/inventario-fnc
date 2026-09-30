<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Crear administrador inicial (solo si no existe) ──
        $adminEmail = config('system.admin.email');

        if ($adminEmail && !User::where('email', $adminEmail)->exists()) {
            User::create([
                'name'                  => config('system.admin.name', 'Administrador'),
                'email'                 => $adminEmail,
                'password'              => Hash::make(config('system.admin.password', 'ChangeMeOnFirstLogin!')),
                'force_password_change' => config('system.force_password_change', true),
                'email_verified_at'     => now(),
            ]);

            $this->command->info('✔ Administrador inicial creado: ' . $adminEmail);
        } else {
            $this->command->info('⏭ Administrador ya existe, no se creó duplicado.');
        }

        // Sembrar roles, permisos y tipos de recursos tecnológicos
        $this->call([
            RolesAndPermissionsSeeder::class,
            PermisosNuevosModulosSeeder::class,
            TipoRecursoSeeder::class,
        ]);

        if (isset($adminEmail) && $adminEmail) {
            $user = User::where('email', $adminEmail)->first();
            if ($user && !$user->hasRole('Administrador')) {
                $user->assignRole('Administrador');
            }
        }
    }
}
