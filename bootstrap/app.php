<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'prevent-back-history' => \App\Http\Middleware\PreventBackHistory::class,
            'force-password-change' => \App\Http\Middleware\ForcePasswordChange::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        
        // Agregar middleware global para capitalizar la primera letra
        $middleware->append(\App\Http\Middleware\CapitalizeFirstLetter::class);

        $middleware->web(append: [
            \App\Http\Middleware\TabContextMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            return response()->view('errors.403', ['exception' => $e], 403);
        });

        // Asegurar que cuando un usuario no autenticado es redirigido al login, no pierda el _tab
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            $tab = $request->input('_tab') ?: $request->query('_tab');
            $url = route('login');
            if ($tab) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . '_tab=' . $tab;
            }
            return redirect()->guest($url);
        });
    })->create();
