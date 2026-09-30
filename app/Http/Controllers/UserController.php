<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        return view('usuarios.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::select('id', 'name')->get();
        return view('usuarios.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.exists' => 'El rol seleccionado no es válido.',
        ]);

        if ($request->role === 'Administrador' && !Auth::user()->hasRole('Administrador')) {
            return redirect()->back()->with('error', 'No tienes permisos para asignar el rol de Administrador.')->withInput();
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole($request->role);

            AuditLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Sistema',
                'action' => 'Cambio de rol a usuario',
                'details' => 'Se asignó el rol: ' . $request->role . ' al usuario: ' . $user->name
            ]);

            DB::commit();
            return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al crear el usuario.');
        }
    }

    public function edit(User $usuario)
    {
        if ($usuario->hasRole('Administrador') && !Auth::user()->hasRole('Administrador')) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para editar a un administrador.');
        }

        $roles = Role::select('id', 'name')->get();
        return view('usuarios.form', ['user' => $usuario, 'roles' => $roles]);
    }

    public function update(Request $request, User $usuario)
    {
        if ($usuario->hasRole('Administrador') && !Auth::user()->hasRole('Administrador')) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para modificar a un administrador.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$usuario->id],
            'role' => ['required', 'exists:roles,name'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.exists' => 'El rol seleccionado no es válido.',
        ]);

        if ($request->role === 'Administrador' && !Auth::user()->hasRole('Administrador')) {
            return redirect()->back()->with('error', 'No tienes permisos para asignar el rol de Administrador.')->withInput();
        }

        DB::beginTransaction();
        try {
            $usuario->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            if ($request->filled('password')) {
                $request->validate([
                    'password' => ['confirmed', Rules\Password::defaults()],
                ], [
                    'password.confirmed' => 'La confirmación de la contraseña no coincide.',
                ]);
                $usuario->update(['password' => Hash::make($request->password)]);
            }

            $oldRole = $usuario->roles->first()->name ?? 'Ninguno';
            $usuario->syncRoles([$request->role]);

            if ($oldRole !== $request->role) {
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name ?? 'Sistema',
                    'action' => 'Cambio de rol a usuario',
                    'details' => 'Se cambió el rol de ' . $oldRole . ' a ' . $request->role . ' para el usuario: ' . $usuario->name
                ]);
            }

            DB::commit();
            return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar el usuario.');
        }
    }

    public function destroy(User $usuario)
    {
        if ($usuario->hasRole('Administrador') && !Auth::user()->hasRole('Administrador')) {
            return redirect()->route('usuarios.index')->with('error', 'No tienes permisos para eliminar a un administrador.');
        }

        if ($usuario->id === Auth::id()) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }
        $usuario->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado exitosamente.');
    }
}
