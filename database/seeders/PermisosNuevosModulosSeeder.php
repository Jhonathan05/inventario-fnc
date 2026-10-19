<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permisos de módulos incorporados después de RolesAndPermissionsSeeder.
 *
 * Motivo: hay rutas protegidas con `permission:` que no existían como permiso
 * (p. ej. `licencias.exportar` y `mesaayuda.eliminar`). Con Spatie, un permiso
 * inexistente es denegado siempre, así que esas rutas eran inalcanzables
 * incluso para el Administrador.
 *
 * `tests/Feature/PermissionsConsistencyTest.php` verifica que ningún permiso
 * usado en las rutas quede sin definir.
 *
 * Es idempotente: puede ejecutarse cuantas veces se quiera.
 */
class PermisosNuevosModulosSeeder extends Seeder
{
    /**
     * Permisos nuevos y su asignación por rol.
     *
     * Lote 19: el Analista TIC se iguala al Administrador, así que
     * `licencias.exportar` también le corresponde. `mesaayuda.eliminar` era
     * de ambos.
     *
     * @var array<string, array<int, string>> permiso => roles
     */
    private array $permisos = [
        'licencias.exportar' => ['Administrador', 'Analista Tic'],
        'mesaayuda.eliminar' => ['Administrador', 'Analista Tic'],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permisos as $permiso => $roles) {
            Permission::firstOrCreate(['name' => $permiso]);

            foreach ($roles as $rol) {
                Role::firstOrCreate(['name' => $rol])->givePermissionTo($permiso);
            }
        }
    }
}
