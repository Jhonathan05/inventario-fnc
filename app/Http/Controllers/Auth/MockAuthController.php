<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mock\MockUsers;
use App\Services\Auth\IdentityProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Mock auth offline por perfiles (skill fnc-mock-auth).
 *
 * Doble candado dev-only: middleware `mock.auth` (403 si el flag está apagado
 * o la app corre en producción) + `config('mock.enabled')` en cada acción.
 * Misma sesión y mismo gate RBAC que el login real: sólo cambia el origen.
 */
class MockAuthController extends Controller
{
    public function __construct(private readonly IdentityProvisioner $provisioner)
    {
    }

    /**
     * Selector de perfiles.
     */
    public function show(Request $request): View
    {
        abort_unless(config('mock.enabled'), 403, 'Mock deshabilitado');

        return view('auth.login-mock', [
            'profiles' => MockUsers::all(),
            'selected' => MockUsers::find($request->query('role')),
        ]);
    }

    /**
     * Autentica al perfil seleccionado.
     */
    public function login(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(config('mock.enabled'), 403, 'Mock deshabilitado');

        $profile = MockUsers::find($request->input('role'));

        abort_if($profile === null, 400, 'Perfil mock inválido');

        $user = $this->provisioner->provision(
            $profile['email'],
            $profile['nombre'],
            $profile['role'],
        );

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'mock' => true,
                'user' => [
                    'email' => $user->email,
                    'nombre' => $user->name,
                    'role' => $profile['role'],
                ],
            ]);
        }

        // Indicador MODO MOCK en el layout autenticado (skill fnc-mock-auth).
        $request->session()->put('mock_auth', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Cierra la sesión mock.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(config('mock.enabled'), 403, 'Mock deshabilitado');

        Auth::guard('web')->logout();
        $request->session()->forget('mock_auth');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'mock' => true]);
        }

        return redirect()->route('login');
    }
}
