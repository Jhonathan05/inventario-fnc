<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TabContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Ignorar rutas que no necesitan tab context (API, assets, debugbar)
        if ($request->is('api/*') || $request->is('build/*') || $request->is('_debugbar/*')) {
            return $next($request);
        }

        $tab = $request->input('_tab') ?: $request->query('_tab');

        if (!$tab) {
            // Si es una petición GET estándar y no tiene _tab, se lo asignamos
            // No podemos redirigir POST porque se perderían los datos, pero los POST deben venir con _tab desde el formulario
            if ($request->isMethod('GET') && !$request->ajax()) {
                $tab = Str::random(8);
                return redirect($request->fullUrlWithQuery(['_tab' => $tab]));
            }
        } else {
            // Aplicar _tab por defecto a todas las URLs generadas vía route()
            URL::defaults(['_tab' => $tab]);
        }

        $response = $next($request);

        // Interceptar todas las redirecciones del servidor para asegurar que no pierdan el contexto de la pestaña
        // Especialmente útil cuando Auth::middleware() redirige a /login (el cual no usa _tab por defecto)
        // o cuando AuthenticatedSessionController redirige a /dashboard
        if ($response instanceof \Illuminate\Http\RedirectResponse && $tab) {
            $url = $response->getTargetUrl();
            if (!str_contains($url, '_tab=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $response->setTargetUrl($url . $separator . '_tab=' . $tab);
            }
        }

        return $response;
    }
}
