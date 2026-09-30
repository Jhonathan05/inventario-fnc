<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        // Agrupar permisos por prefijo (ej: equipos, usuarios, etc)
        $permissions = Permission::select('id', 'name')->get()->groupBy(function($data) {
            return explode('.', $data->name)[0];
        });
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array'
        ], [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Este nombre de rol ya existe.'
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->name]);
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Sistema',
                'action' => 'Creación de roles',
                'details' => 'Se creó el rol: ' . $role->name
            ]);

            DB::commit();
            return redirect()->route('roles.index')->with('success', 'Rol creado exitosamente.');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error: Uno o más permisos seleccionados no existen en el sistema.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error inesperado al crear el rol. Por favor, intente de nuevo.');
        }
    }

    public function edit(Role $role)
    {
        if ($role->name === 'Administrador' && !Auth::user()->hasRole('Administrador')) {
            return redirect()->route('roles.index')->with('error', 'No tienes permisos para editar el rol de Administrador.');
        }

        $permissions = Permission::select('id', 'name')->get()->groupBy(function($data) {
            return explode('.', $data->name)[0];
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'Administrador' && !Auth::user()->hasRole('Administrador')) {
            return redirect()->route('roles.index')->with('error', 'No tienes permisos para modificar el rol de Administrador.');
        }

        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array'
        ], [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Este nombre de rol ya existe.'
        ]);

        DB::beginTransaction();
        try {
            $role->name = $request->name;
            $role->save();
            
            $permissionsToSync = $request->permissions ?? [];
            $role->syncPermissions($permissionsToSync);

            AuditLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Sistema',
                'action' => 'Edición de roles',
                'details' => 'Se editó el rol: ' . $role->name . ' y sus permisos'
            ]);

            DB::commit();
            return redirect()->route('roles.index')->with('success', 'Rol actualizado exitosamente.');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error: Uno o más permisos seleccionados no existen en el sistema.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error inesperado al actualizar el rol. Por favor, intente de nuevo.');
        }
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Administrador' && !Auth::user()->hasRole('Administrador')) {
            return redirect()->route('roles.index')->with('error', 'No tienes permisos para eliminar el rol de Administrador.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', 'No se puede eliminar el rol porque tiene usuarios asociados.');
        }

        $roleName = $role->name;
        $role->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Sistema',
            'action' => 'Eliminación de roles',
            'details' => 'Se eliminó el rol: ' . $roleName
        ]);

        return redirect()->route('roles.index')->with('success', 'Rol eliminado exitosamente.');
    }
}
