<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcRespuesta extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_respuestas';

    protected $fillable = [
        'intento_id',
        'pregunta_id',
        'opcion_id',
        'texto_respuesta',
        'es_correcta',
        'puntos_obtenidos',
    ];

    protected $casts = [
        'es_correcta'      => 'boolean',
        'puntos_obtenidos' => 'float',
    ];

    public function intento()
    {
        return $this->belongsTo(ZcIntento::class, 'intento_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(ZcPregunta::class, 'pregunta_id');
    }

    public function opcion()
    {
        return $this->belongsTo(ZcOpcion::class, 'opcion_id');
    }
}
