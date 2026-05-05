<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
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
        //
    }
}
