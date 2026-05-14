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

        $adminEmail = env('HORIZON_ALLOWED_EMAILS');
        if ($adminEmail) {
            Horizon::routeMailNotificationsTo(explode(',', $adminEmail)[0]);
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
            if (method_exists($user, 'hasRole') && $user->hasRole('SuperAdmin')) {
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
