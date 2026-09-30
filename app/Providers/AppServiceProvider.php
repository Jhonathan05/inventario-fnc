<?php

namespace App\Providers;

use App\Models\SolicitudCambioPassword;
use App\Policies\SolicitudCambioPasswordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(SolicitudCambioPassword::class, SolicitudCambioPasswordPolicy::class);
        \App\Models\ActivoComplemento::observe(\App\Observers\ActivoComplementoObserver::class);

        \Illuminate\Support\Facades\Auth::extend('tab_session', function ($app, $name, array $config) {
            $provider = \Illuminate\Support\Facades\Auth::createUserProvider($config['provider'] ?? null);
            $guard = new \App\Auth\TabSessionGuard($name, $provider, $app['session.store'], $app['request']);
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            return $guard;
        });
    }
}
