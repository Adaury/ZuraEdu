<?php

namespace App\Providers;

use App\Models\User;
use App\Models\AlertaSistema;
use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\CalificacionAcademica;
use App\Observers\CalificacionAcademicaObserver;
use App\Observers\CalificacionObserver;
use App\Observers\EstudianteObserver;
use App\Observers\MatriculaObserver;
use App\Helpers\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;

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
        // Detecta N+1 queries en desarrollo — lanza excepción si se lazy-load una relación
        Model::preventLazyLoading(! app()->isProduction());

        // Forzar HTTPS solo si se activa explícitamente (FORCE_HTTPS=true en .env)
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();

        // Register model observers
        Calificacion::observe(CalificacionObserver::class);
        Estudiante::observe(EstudianteObserver::class);
        CalificacionAcademica::observe(CalificacionAcademicaObserver::class);

        // Share data with the admin layout — cached to avoid repeated queries on every request
        View::composer('layouts.admin', function ($view) {
            try {
                $usuariosPendientes = Cache::remember('t' . (tenant_id() ?? 0) . '_usuarios_pendientes_count', 300, fn () =>
                    User::where('pendiente_aprobacion', true)->count()
                );
            } catch (\Exception $e) {
                $usuariosPendientes = 0;
            }

            $alertasNoLeidas = 0;
            try {
                if (Auth::check()) {
                    $user = Auth::user();
                    $rol  = $user->roles->first()?->name;
                    $alertasNoLeidas = Cache::remember('t' . (tenant_id() ?? 0) . "_alertas_no_leidas_{$user->id}", 120, fn () =>
                        AlertaSistema::noLeidas()
                            ->vigentes()
                            ->paraUsuario($user->id, $rol)
                            ->count()
                    );
                }
            } catch (\Exception $e) {
                $alertasNoLeidas = 0;
            }

            // System branding — usa Setting (Redis-cached + PHP memoized, 0 queries extra por request)
            try {
                $systemSettings = [
                    'system_name' => Setting::get('system_name', 'Zura'),
                    'system_abbr' => Setting::get('system_abbr', 'SGE'),
                    'system_sub'  => Setting::get('system_sub',  'Gestión Escolar'),
                    'system_logo' => Setting::get('system_logo'),
                ];
            } catch (\Exception $e) {
                $systemSettings = [
                    'system_name' => 'Zura',
                    'system_abbr' => 'SGE',
                    'system_sub'  => 'Gestión Escolar',
                    'system_logo' => null,
                ];
            }

            $view->with('usuariosPendientes', $usuariosPendientes)
                 ->with('alertasNoLeidas', $alertasNoLeidas)
                 ->with('systemSettings', $systemSettings);
        });
    }
}
