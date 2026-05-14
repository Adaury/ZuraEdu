<?php

namespace App\Traits;

use App\Models\Docente;

trait HasDocenteContext
{
    protected function getDocente(): Docente
    {
        $docente = Docente::where('user_id', auth()->id())->first();
        abort_unless($docente, 403, 'No tienes un perfil de docente asociado.');
        return $docente;
    }
}
