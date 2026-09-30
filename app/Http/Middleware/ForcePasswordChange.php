<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Rutas que el usuario puede visitar incluso con force_password_change = true.
     * Esto evita bucles de redirección y permite cerrar sesión.
     */
    protected array $except = [
        'password.force-change',
        'password.force-change.update',
        'logout',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->force_password_change) {
            $currentRoute = $request->route()?->getName();

            if (!in_array($currentRoute, $this->except, true)) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
