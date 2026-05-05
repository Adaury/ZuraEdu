<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpcionPregunta extends Model
{
    use BelongsToTenant;

    protected $table = 'opciones_pregunta';

    protected $fillable = [
        'pregunta_id',
        'texto',
        'orden',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(PreguntaEncuesta::class, 'pregunta_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaEncuesta::class, 'opcion_id');
    }
}
