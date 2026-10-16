<?php

namespace App\Mock;

/**
 * Catálogo de perfiles mock (skill fnc-mock-auth).
 *
 * Usuarios 100% sintéticos: dominios `example.test`, jamás cuentas reales
 * del Comité. El mock sólo cambia *cómo se obtiene* la sesión; el gate RBAC
 * (roles/permisos Spatie) sigue siendo el mismo código que en producción.
 */
class MockUsers
{
    /**
     * @var array<int, array{key:string, role:string, email:string, nombre:string, descripcion:string}>
     */
    public const PROFILES = [
        [
            'key' => 'administrador',
            'role' => 'Administrador',
            'email' => 'administrador.mock@example.test',
            'nombre' => 'Administrador Mock',
            'descripcion' => 'Acceso total: equipos, licencias, usuarios, configuración y roles.',
        ],
        [
            'key' => 'analista-tic',
            'role' => 'Analista Tic',
            'email' => 'analista.tic.mock@example.test',
            'nombre' => 'Analista TIC Mock',
            'descripcion' => 'Mesa de ayuda, equipos, inventario, checklists e historiales.',
        ],
    ];

    /**
     * @return array<int, array{key:string, role:string, email:string, nombre:string, descripcion:string}>
     */
    public static function all(): array
    {
        return self::PROFILES;
    }

    /**
     * Busca un perfil por su clave (`administrador`) o por su rol (`Administrador`).
     *
     * @return array{key:string, role:string, email:string, nombre:string, descripcion:string}|null
     */
    public static function find(?string $key): ?array
    {
        $needle = mb_strtolower(trim((string) $key));

        if ($needle === '') {
            return null;
        }

        foreach (self::PROFILES as $profile) {
            if (mb_strtolower($profile['key']) === $needle || mb_strtolower($profile['role']) === $needle) {
                return $profile;
            }
        }

        return null;
    }
}
