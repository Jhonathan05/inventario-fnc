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
     * - `licencias.exportar`: el módulo de licencias es exclusivo de
     *   Administrador (ver RolesAndPermissionsSeeder).
     * - `mesaayuda.eliminar`: la mesa de ayuda la operan los analistas, que ya
     *   cuentan con ver/crear/editar/cerrar; se les habilita también eliminar.
     *
     * @var array<string, array<int, string>> permiso => roles
     */
    private array $permisos = [
        'licencias.exportar' => ['Administrador'],
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
