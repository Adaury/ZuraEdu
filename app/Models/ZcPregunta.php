<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcPregunta extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_preguntas';

    protected $fillable = ['quiz_id', 'enunciado', 'tipo', 'puntos', 'orden', 'imagen'];

    protected $casts = ['puntos' => 'float'];

    public function quiz()
    {
        return $this->belongsTo(ZcQuiz::class, 'quiz_id');
    }

    public function opciones()
    {
        return $this->hasMany(ZcOpcion::class, 'pregunta_id')->orderBy('orden');
    }

    public function respuestas()
    {
        return $this->hasMany(ZcRespuesta::class, 'pregunta_id');
    }

    public function opcionCorrecta(): ?ZcOpcion
    {
        return $this->opciones()->where('es_correcta', true)->first();
    }
}
