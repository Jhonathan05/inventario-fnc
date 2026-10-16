<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\KeycloakController;
use App\Http\Controllers\Auth\MockAuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\SolicitudCambioPasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::post('solicitudes-cambio-password', [SolicitudCambioPasswordController::class, 'storePublic'])
        ->middleware('throttle:3,1')
        ->name('solicitudes-password.store-public');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.store');
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
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
