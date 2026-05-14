<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Devuelve los períodos del año escolar usando caché tenant-scoped (10 min).
     * Centralizado aquí para evitar duplicación en todos los controladores.
     */
    protected function getPeriodos(?SchoolYear $schoolYear): Collection
    {
        if (! $schoolYear) return new Collection();
        $tid  = tenant_id() ?? 0;
        $syId = $schoolYear->id;
        return Cache::remember("t{$tid}_periodos_{$syId}", 600,
            fn() => Periodo::where('school_year_id', $syId)->orderBy('numero')->get()
        );
    }
}
