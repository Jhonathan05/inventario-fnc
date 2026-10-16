<?php

namespace Tests\Feature;

use Database\Seeders\PermisosNuevosModulosSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Guarda de consistencia RBAC.
 *
 * Un `permission:` en una ruta que no existe como permiso es denegado siempre
 * por Spatie, dejando la ruta inalcanzable incluso para el Administrador. Este
 * test evita que vuelva a introducirse ese fallo.
 */
class PermissionsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_todos_los_permisos_usados_en_rutas_estan_definidos(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PermisosNuevosModulosSeeder::class);

        $usados = collect(app('router')->getRoutes()->getRoutes())
            ->flatMap(fn ($route) => collect($route->gatherMiddleware())
                ->filter(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'permission:'))
                ->flatMap(function ($middleware) {
                    $expression = str($middleware)->after('permission:')->toString();

                    // Spatie admite `permission:a|b` y `permission:a,b` (cualquiera).
                    return preg_split('/[|,]/', $expression) ?: [];
                })
            )
            ->map(fn ($permiso) => trim($permiso))
            ->filter(fn ($permiso) => str_contains($permiso, '.'))
            ->unique()
            ->values();

        $this->assertNotEmpty($usados, 'No se detectó ningún permiso en las rutas: revisa el análisis.');

        $definidos = Permission::whereIn('name', $usados)->pluck('name');
        $faltantes = $usados->diff($definidos)->values();

        $this->assertTrue(
            $faltantes->isEmpty(),
            'Hay permisos usados en rutas sin definir (serían rutas inalcanzables): '
                . $faltantes->implode(', ')
        );
    }

    public function test_el_administrador_tiene_todos_los_permisos(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PermisosNuevosModulosSeeder::class);

        $admin = \Spatie\Permission\Models\Role::findByName('Administrador');

        $sinAsignar = Permission::pluck('name')->diff($admin->permissions->pluck('name'));

        $this->assertTrue(
            $sinAsignar->isEmpty(),
            'El rol Administrador no tiene: ' . $sinAsignar->implode(', ')
        );
    }
}
