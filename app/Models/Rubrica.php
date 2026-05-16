<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubrica extends Model
{
    use BelongsToTenant;

    protected $table = 'rubricas';

    protected $fillable = [
        'docente_id', 'asignatura_id', 'titulo', 'descripcion', 'niveles', 'criterios',
    ];

    protected $casts = [
        'niveles'   => 'array',
        'criterios' => 'array',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(RubricaAplicacion::class);
    }

    public function getPuntajeMaxAttribute(): float
    {
        return collect($this->criterios)->sum('puntos');
    }

    public function calcularPuntaje(array $resultados): float
    {
        $total   = 0;
        $niveles = $this->niveles;
        $maxPct  = max(array_column($niveles, 'pct'));

        foreach ($this->criterios as $i => $crit) {
            $nivelIdx = $resultados[$i] ?? null;
            if ($nivelIdx !== null && isset($niveles[$nivelIdx])) {
                $pct    = $niveles[$nivelIdx]['pct'];
                $total += $crit['puntos'] * ($pct / 100);
            }
        }

        return round($total, 2);
    }
}
