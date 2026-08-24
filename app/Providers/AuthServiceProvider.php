<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Asignacion;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Policies\AsignacionPolicy;
use App\Policies\EstudiantePolicy;
use App\Policies\GrupoPolicy;
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
        Estudiante::class => EstudiantePolicy::class,
        Grupo::class      => GrupoPolicy::class,
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
    }
}
