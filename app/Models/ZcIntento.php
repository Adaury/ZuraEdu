<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcIntento extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_intentos';

    protected $fillable = [
        'quiz_id',
        'matricula_id',
        'estado',
        'puntuacion',
        'puntuacion_max',
        'iniciado_en',
        'finalizado_en',
        'numero_intento',
    ];

    protected $casts = [
        'iniciado_en'   => 'datetime',
        'finalizado_en' => 'datetime',
        'puntuacion'    => 'float',
        'puntuacion_max'=> 'float',
    ];

    public function quiz()
    {
        return $this->belongsTo(ZcQuiz::class, 'quiz_id');
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function respuestas()
    {
        return $this->hasMany(ZcRespuesta::class, 'intento_id');
    }

    public function getPorcentajeAttribute(): float
    {
        if (!$this->puntuacion_max) return 0;
        return round(($this->puntuacion / $this->puntuacion_max) * 100, 1);
    }

    public function getDuracionAttribute(): ?string
    {
        if (!$this->finalizado_en || !$this->iniciado_en) return null;
        $min = $this->iniciado_en->diffInMinutes($this->finalizado_en);
        return $min . ' min';
    }
}
