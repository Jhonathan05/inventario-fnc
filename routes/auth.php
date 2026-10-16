<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\KeycloakController;
use App\Http\Controllers\Auth\MockAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\SolicitudCambioPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Acceso
|--------------------------------------------------------------------------
| El acceso es institucional (Keycloak OIDC) o, en desarrollo/E2E, por
| perfiles mock. El registro público y los flujos de contraseña de Breeze
| (recuperar, restablecer, confirmar y cambiar contraseña local) fueron
| retirados: las credenciales las administra el proveedor de identidad.
| Ver docs/contexto.md §7.
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Deshabilitado por defecto: responde 403 salvo LOCAL_LOGIN_ENABLED=true
    // (soporte/transición).
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Solicitudes de cambio de contraseña: flujo propio (no Breeze). Se mantiene
    // público para que quien no puede entrar pueda pedir el cambio a soporte.
    Route::post('solicitudes-cambio-password', [SolicitudCambioPasswordController::class, 'storePublic'])
        ->middleware('throttle:3,1')
        ->name('solicitudes-password.store-public');
});

/*
|--------------------------------------------------------------------------
| Acceso institucional — Keycloak OIDC (skill fnc-keycloak-oidc-agenda)
|--------------------------------------------------------------------------
| Authorization Code + PKCE. El callback vive fuera del grupo `guest` para no
| rebotar cuando Keycloak devuelve al usuario con sesión previa activa.
*/
Route::get('auth/keycloak', [KeycloakController::class, 'redirect'])->name('auth.keycloak');
Route::get('auth/keycloak/callback', [KeycloakController::class, 'callback'])->name('auth.keycloak.callback');

/*
|--------------------------------------------------------------------------
| Mock offline por perfiles (skill fnc-mock-auth) — dev-only
|--------------------------------------------------------------------------
| Doble candado: el middleware `mock.auth` responde 403 si el flag está
| apagado o si la app corre en producción.
*/
Route::middleware('mock.auth')->group(function () {
    // Contrato de la skill: selector en /login-mock.
    Route::get('login-mock', [MockAuthController::class, 'show'])->name('mock.login');

    Route::prefix(config('mock.route_prefix', 'mock-auth'))->group(function () {
        Route::get('login-mock', [MockAuthController::class, 'show'])->name('mock.login.prefixed');
        Route::post('mock-login', [MockAuthController::class, 'login'])->name('mock.login.store');
        Route::post('mock-logout', [MockAuthController::class, 'logout'])->name('mock.logout');
    });
});

Route::middleware('auth')->group(function () {
    // Verificación de correo de la app (middleware `verified`). Los usuarios de
    // Keycloak y del mock se aprovisionan ya verificados; se mantiene para no
    // romper el `verified` de los grupos de rutas autenticadas.
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
