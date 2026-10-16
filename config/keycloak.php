<?php

/*
|--------------------------------------------------------------------------
| Keycloak / OIDC institucional (skill fnc-keycloak-oidc-agenda)
|--------------------------------------------------------------------------
|
| Topología dual de red:
|   - KEYCLOAK_URL        → backchannel (server-to-server: discovery, token,
|                           JWKS) sobre la red interna de Docker.
|   - KEYCLOAK_PUBLIC_URL → frontchannel (redirección del navegador) a través
|                           del túnel público / Cloudflare.
|
| El emisor (`iss`) que valida la app es el público.
|
*/

return [

    // Interruptor principal. Con AUTH_PROVIDER=keycloak queda activo.
    'enabled' => filter_var(env('KEYCLOAK_ENABLED', false), FILTER_VALIDATE_BOOL),

    'base_url' => env('KEYCLOAK_URL', 'http://fnc-keycloak-s4:8080/auth'),

    'public_base_url' => env('KEYCLOAK_PUBLIC_URL', env('KEYCLOAK_URL', 'http://fnc-keycloak-s4:8080/auth')),

    'realm' => env('KEYCLOAK_REALM', 'fnc-realm'),

    'client_id' => env('KEYCLOAK_CLIENT_ID', 'inventario-fnc'),

    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),

    'scopes' => env('KEYCLOAK_SCOPES', 'openid profile email'),

    'timeout' => (int) env('KEYCLOAK_TIMEOUT', 10),

    /*
    | Jerarquía completa de roles FNC (client roles en Keycloak).
    | Se resuelve el rol de mayor peso y se traduce al rol local (Spatie).
    */
    'role_hierarchy' => [
        'ADMIN' => 70,
        'DIRECTOR' => 60,
        'DIRECCION' => 50,
        'LIDER' => 40,
        'COORDINADOR' => 30,
        'ASISTENCIAL' => 20,
        'FUNCIONARIO' => 10,
    ],

    'role_map' => [
        'ADMIN' => 'Administrador',
        'DIRECTOR' => 'Administrador',
        'DIRECCION' => 'Administrador',
        'LIDER' => 'Analista Tic',
        'COORDINADOR' => 'Analista Tic',
        'ASISTENCIAL' => 'Analista Tic',
        'FUNCIONARIO' => 'Analista Tic',
    ],

    // Rol local asignado cuando Keycloak no expone un rol reconocible.
    'default_role' => env('KEYCLOAK_DEFAULT_ROLE', 'Analista Tic'),

    // A dónde vuelve el navegador tras el cierre de sesión SSO.
    'post_logout_redirect' => env('KEYCLOAK_POST_LOGOUT_REDIRECT', '/login'),

];
