<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'dashboard.ver',
            
            // Equipos
            'equipos.ver', 'equipos.crear', 'equipos.editar', 'equipos.eliminar', 'equipos.exportar', 'equipos.importar',
            
            // Usuarios Asignados
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            
            // Checklists
            'checklist.ver', 'checklist.crear', 'checklist.editar', 'checklist.eliminar',
            
            // Licencias
            'licencias.ver', 'licencias.crear', 'licencias.editar', 'licencias.eliminar',
            
            // Mesa de Ayuda
            'mesaayuda.ver', 'mesaayuda.crear', 'mesaayuda.editar', 'mesaayuda.cerrar',
            
            // Historiales
            'historial.ver', 'historial.exportar',
            
            // Configuración
            'configuracion.ver', 'configuracion.editar', 'campos_personalizados.ver', 'campos_personalizados.crear', 'campos_personalizados.editar', 'campos_personalizados.eliminar',
            
            // Roles
            'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign created permissions

        // 1. Administrador (acceso total)
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador']);
        $roleAdmin->syncPermissions(Permission::all());

        // 2. Analista Tic (Mesa de Ayuda, Equipos, Historiales, etc.)
        $roleAnalista = Role::firstOrCreate(['name' => 'Analista Tic']);
        $roleAnalista->syncPermissions([
            'dashboard.ver',
            'mesaayuda.ver', 'mesaayuda.crear', 'mesaayuda.editar', 'mesaayuda.cerrar',
            'equipos.ver', 'equipos.crear', 'equipos.editar', 'equipos.eliminar', 'equipos.exportar', 'equipos.importar',
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'historial.ver', 'historial.exportar',
            'checklist.ver', 'checklist.crear', 'checklist.editar', 'checklist.eliminar'
        ]);
    }
}
