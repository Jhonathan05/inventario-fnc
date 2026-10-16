<?php

/*
|--------------------------------------------------------------------------
| Mock auth (skill fnc-mock-auth)
|--------------------------------------------------------------------------
|
| Doble candado dev-only: el flag debe estar encendido Y la app no puede
| estar en producción. Si cualquiera falla, TODA ruta mock responde 403.
|
*/

return [

    // Doble candado dev-only. OJO: dentro de un archivo de config sólo puede
    // usarse env() — llamar a app() rompe `php artisan config:cache`.
    'enabled' => filter_var(env('MOCK_AUTH', false), FILTER_VALIDATE_BOOL)
        && env('APP_ENV', 'production') !== 'production',

    'login_path' => '/login-mock',

    // Prefijo del grupo de rutas mock (routes/auth.php).
    'route_prefix' => 'mock-auth',

];
