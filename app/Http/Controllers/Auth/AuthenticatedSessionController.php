<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Keycloak\KeycloakClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * Con el mock activo (dev/E2E) la entrada de acceso es el selector de
     * perfiles; en cualquier otro caso, el acceso institucional (Keycloak).
     * Nunca se muestran ambas puertas a la vez.
     */
    public function create(): View
    {
        if (config('mock.enabled')) {
            return view('auth.login-mock', [
                'profiles' => \App\Mock\MockUsers::all(),
                'selected' => null,
            ]);
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Login local deshabilitado por defecto (Opción B: login único + SSO).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        abort_unless(config('auth.local_login_enabled'), 403, 'Login local deshabilitado');

        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // En un entorno de sesiones aisladas por pestaña, no podemos invalidar 
        // toda la sesión compartida ni regenerar el token CSRF, porque esto 
        // destruiría o invalidaría las demás pestañas activas.
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        // Cierre de sesión SSO (RP-initiated logout): sin esto la sesión de
        // Keycloak seguiría viva y el siguiente login entraría sin credenciales.
        if (config('auth.provider') === 'keycloak') {
            $idToken = $request->cookie('kc_id_token');

            $endSessionUrl = KeycloakClient::fromConfig()->endSessionUrl(
                $idToken ? (string) $idToken : null,
                url(config('keycloak.post_logout_redirect', '/login')),
            );

            if ($endSessionUrl) {
                return redirect()->away($endSessionUrl)
                    ->withCookie(cookie()->forget('kc_id_token'));
            }
        }

        return redirect('/');
    }
}
