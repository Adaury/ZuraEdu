<?php

namespace App\Providers;

use App\Models\User;
use App\Models\AlertaSistema;
use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\CalificacionAcademica;
use App\Observers\CalificacionObserver;
use App\Observers\EstudianteObserver;
use App\Observers\CalificacionAcademicaObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
        // Forzar HTTPS solo si se activa explícitamente (FORCE_HTTPS=true en .env)
        // Útil en producción detrás de proxies/balanceadores sin modificar APP_ENV
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
                $usuariosPendientes = Cache::remember('usuarios_pendientes_count', 300, fn () =>
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
                    $alertasNoLeidas = Cache::remember("alertas_no_leidas_{$user->id}", 120, fn () =>
                        AlertaSistema::noLeidas()
                            ->vigentes()
                            ->paraUsuario($user->id, $rol)
                            ->count()
                    );
                }
            } catch (\Exception $e) {
                $alertasNoLeidas = 0;
            }

            // System branding settings (cached 10 min)
            try {
                $systemSettings = Cache::remember('system_settings_branding', 600, function () {
                    $rows = \Illuminate\Support\Facades\DB::table('system_settings')
                        ->whereIn('key', ['system_name', 'system_abbr', 'system_logo', 'system_sub'])
                        ->pluck('value', 'key');
                    return [
                        'system_name' => $rows['system_name'] ?? 'Zura',
                        'system_abbr' => $rows['system_abbr'] ?? 'SGE',
                        'system_sub'  => $rows['system_sub']  ?? 'Gestión Escolar',
                        'system_logo' => $rows['system_logo'] ?? null,
                    ];
                });
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
