<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Doble candado dev-only del mock (skill fnc-mock-auth).
 *
 * Si `config('mock.enabled')` es falso — flag apagado o entorno de producción —
 * TODA ruta del grupo mock responde 403 y la app se comporta igual que si el
 * mock no existiera.
 */
class MockAuthEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('mock.enabled'), 403, 'Mock deshabilitado');

        return $next($request);
    }
}
