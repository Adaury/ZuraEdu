<?php

namespace App\Observers;

use App\Events\DashboardActualizado;
use App\Models\Matricula;

class MatriculaObserver
{
    public function created(Matricula $matricula): void
    {
        try {
            $tenantId = tenant_id() ?? 0;
            DashboardActualizado::dispatch($tenantId, 'nueva_matricula', [
                'grupo_id' => $matricula->grupo_id,
            ]);
        } catch (\Throwable) {}
    }
}
