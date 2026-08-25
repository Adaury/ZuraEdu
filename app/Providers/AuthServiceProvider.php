<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Asignacion;
use App\Models\Matricula;
use App\Policies\AsignacionPolicy;
use App\Policies\BoletinPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Asignacion::class => AsignacionPolicy::class,
        Matricula::class  => BoletinPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // SuperAdmin "entra al panel admin de una institución" (TenantController::enterPanel)
        // y debe poder administrarla por completo, sin depender de los permisos granulares
        // por tenant que solo tienen sentido para el staff propio de cada institución.
        Gate::before(function ($user, string $ability) {
            return $user?->hasRole('super_admin') ? true : null;
        });

        // Módulos sensibles sin permiso Spatie dedicado (Fichas de Salud, Disciplina):
        // se definen como Gates en vez de middleware role: directo para que el
        // Gate::before de super_admin arriba también les aplique de forma consistente.
        Gate::define('acceso-salud-disciplina', function ($user) {
            return $user->hasAnyRole([
                'Administrador', 'Director', 'Registrador Académico', 'Encargado de Registro Académico',
            ]);
        });

        Gate::define('acceso-billing', fn ($user) => $user->hasRole('Administrador'));

        // Reemplazan middleware('role:...') usado directamente en rutas: ese middleware
        // no pasa por el sistema de Gates y por lo tanto el Gate::before de super_admin
        // de arriba NO lo intercepta (ver docs/MATRIZ_PERMISOS_ZURAEDU.md §"Hallazgo
        // adicional"). Con Gate::define + can: en la ruta, super_admin sí recibe el bypass.
        Gate::define('solo-administrador', fn ($user) => $user->hasRole('Administrador'));

        Gate::define('acceso-direccion', fn ($user) => $user->hasAnyRole(['Administrador', 'Director']));

        Gate::define('acceso-direccion-coordinacion', function ($user) {
            return $user->hasAnyRole([
                'Administrador', 'Director',
                'Coordinador Académico', 'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo',
            ]);
        });

        // routes/api.php (app móvil) tenía el mismo bug de §2.1 (solo 'Docente' literal,
        // deja fuera a Docente Académico/Técnico/Guía) más el anti-patrón role: de arriba.
        Gate::define('acceso-docente-api', function ($user) {
            return $user->tieneRolDocente() || $user->hasAnyRole(['Administrador', 'Director']);
        });

        Gate::define('acceso-sigerd', function ($user) {
            return $user->hasAnyRole([
                'Administrador', 'Director', 'Coordinador Académico',
                'Registrador Académico', 'Encargado de Registro Académico',
            ]);
        });
    }
}
