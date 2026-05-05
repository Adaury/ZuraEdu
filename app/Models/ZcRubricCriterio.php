<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcRubricCriterio extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_rubric_criterios';

    protected $fillable = ['rubric_id', 'nombre', 'descripcion', 'puntaje_max', 'orden'];

    protected $casts = ['puntaje_max' => 'float'];

    public function rubric()
    {
        return $this->belongsTo(ZcRubric::class, 'rubric_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(ZcRubricCalificacion::class, 'criterio_id');
    }
}
