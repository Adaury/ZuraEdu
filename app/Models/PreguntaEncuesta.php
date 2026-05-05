<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreguntaEncuesta extends Model
{
    use BelongsToTenant;

    protected $table = 'preguntas_encuesta';

    protected $fillable = [
        'encuesta_id',
        'texto',
        'tipo',
        'orden',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function encuesta(): BelongsTo
    {
        return $this->belongsTo(Encuesta::class);
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(OpcionPregunta::class, 'pregunta_id')->orderBy('orden');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaEncuesta::class, 'pregunta_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'opcion_multiple' => 'Opción múltiple',
            'texto_libre'     => 'Texto libre',
            'escala_1_5'      => 'Escala 1–5',
            default           => $this->tipo,
        };
    }

    /** Estadísticas de respuestas para esta pregunta */
    public function estadisticas(): array
    {
        $respuestas = $this->respuestas()->get();

        if ($this->tipo === 'opcion_multiple') {
            $conteos = $respuestas->groupBy('opcion_id')->map->count();
            $total   = $respuestas->count();
            $data    = $this->opciones->map(function ($op) use ($conteos, $total) {
                $cnt = $conteos->get($op->id, 0);
                return [
                    'label'      => $op->texto,
                    'count'      => $cnt,
                    'porcentaje' => $total > 0 ? round($cnt / $total * 100, 1) : 0,
                ];
            });
            return ['tipo' => 'opcion_multiple', 'total' => $total, 'data' => $data];
        }

        if ($this->tipo === 'escala_1_5') {
            $conteos = $respuestas->groupBy('escala_valor')->map->count();
            $total   = $respuestas->count();
            $promedio = $total > 0 ? round($respuestas->avg('escala_valor'), 2) : null;
            $data = collect([1,2,3,4,5])->map(function ($v) use ($conteos, $total) {
                $cnt = $conteos->get($v, 0);
                return [
                    'label'      => (string) $v,
                    'count'      => $cnt,
                    'porcentaje' => $total > 0 ? round($cnt / $total * 100, 1) : 0,
                ];
            });
            return ['tipo' => 'escala_1_5', 'total' => $total, 'promedio' => $promedio, 'data' => $data];
        }

        // texto_libre
        return [
            'tipo'      => 'texto_libre',
            'total'     => $respuestas->count(),
            'textos'    => $respuestas->pluck('respuesta_texto')->filter()->values(),
        ];
    }
}
