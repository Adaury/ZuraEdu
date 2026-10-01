<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // config() y no env(): con `config:cache` (deploy.sh corre `artisan optimize`) env()
        // fuera de config/ devuelve null y los avisos de Horizon nunca se enrutaban.
        $adminEmail = array_values(config('horizon.allowed_emails', []))[0] ?? null;
        if ($adminEmail) {
            Horizon::routeMailNotificationsTo(trim($adminEmail));
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            // SuperAdmin siempre puede ver Horizon
            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                return true;
            }
            // En local/staging, todos los admins autenticados pueden ver Horizon
            if (app()->environment('local', 'staging')) {
                return $user !== null;
            }
            // En producción, solo emails de administradores del sistema
            return in_array($user->email, (array) config('horizon.allowed_emails', []));
        });
    }
}
