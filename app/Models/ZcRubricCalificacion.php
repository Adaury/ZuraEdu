<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcRubricCalificacion extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_rubric_calificaciones';

    protected $fillable = ['entrega_id', 'criterio_id', 'puntaje', 'comentario'];

    protected $casts = ['puntaje' => 'float'];

    public function entrega()
    {
        return $this->belongsTo(EntregaClassroom::class, 'entrega_id');
    }

    public function criterio()
    {
        return $this->belongsTo(ZcRubricCriterio::class, 'criterio_id');
    }
}
