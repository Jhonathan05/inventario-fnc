<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Alta/sincronización idempotente del usuario local a partir de una identidad
 * federada (Keycloak) o mock.
 *
 * Regla de la skill fnc-mock-auth: el mock sólo cambia *cómo se obtiene* la
 * sesión, nunca *qué se permite*. Por eso ambos flujos desembocan aquí y el
 * gate RBAC (roles/permisos Spatie) es exactamente el mismo.
 */
class IdentityProvisioner
{
    /**
     * Resuelve o crea el usuario local y sincroniza su rol.
     *
     * @param  string      $email  Correo institucional (clave idempotente).
     * @param  string|null $name   Nombre para mostrar.
     * @param  string|null $role   Rol local (Spatie) a sincronizar.
     */
    public function provision(string $email, ?string $name = null, ?string $role = null): User
    {
        $email = mb_strtolower(trim($email));

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            // Cuenta federada: sin contraseña utilizable (hash aleatorio).
            $user->password = Hash::make(Str::random(48));
        }

        if ($name !== null && trim($name) !== '' && $user->name !== $name) {
            $user->name = $name;
        }

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        // La contraseña nunca se gestiona en la app para identidades federadas.
        $user->force_password_change = false;

        $user->save();

        if ($role !== null) {
            $this->syncRole($user, $role);
        }

        return $user;
    }

    /**
     * Sincroniza el rol local de forma idempotente y tolerante a concurrencia.
     *
     * Se escribe directo sobre la tabla pivote con `insertOrIgnore` porque
     * `syncRoles()` (detach + attach) falla con violación de clave primaria
     * cuando dos peticiones del mismo usuario compiten a la vez (varios logins
     * mock/E2E en paralelo o callbacks OIDC simultáneos).
     */
    private function syncRole(User $user, string $role): void
    {
        $roleModel = Role::where('name', $role)->first();

        if (! $roleModel) {
            return;
        }

        $table = config('permission.table_names.model_has_roles', 'model_has_roles');

        DB::table($table)->insertOrIgnore([
            'role_id' => $roleModel->getKey(),
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
        ]);

        // El proveedor de identidad es la fuente de verdad: se retiran los
        // demás roles, preservando siempre el vigente.
        DB::table($table)
            ->where('model_id', $user->getKey())
            ->where('model_type', $user->getMorphClass())
            ->where('role_id', '<>', $roleModel->getKey())
            ->delete();

        $user->unsetRelation('roles');
    }
}
