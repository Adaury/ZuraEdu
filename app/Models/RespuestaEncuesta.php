<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespuestaEncuesta extends Model
{
    use BelongsToTenant;

    protected $table = 'respuestas_encuesta';

    protected $fillable = [
        'tenant_id',
        'encuesta_id',
        'pregunta_id',
        'user_id',
        'respuesta_texto',
        'opcion_id',
        'escala_valor',
    ];

    protected $casts = [
        'escala_valor' => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function encuesta(): BelongsTo
    {
        return $this->belongsTo(Encuesta::class);
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(PreguntaEncuesta::class, 'pregunta_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(OpcionPregunta::class, 'opcion_id');
    }
}
