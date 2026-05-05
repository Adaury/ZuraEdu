<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcRubric extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_rubrics';

    protected $fillable = ['material_id', 'nombre', 'descripcion'];

    public function material()
    {
        return $this->belongsTo(MaterialClase::class, 'material_id');
    }

    public function criterios()
    {
        return $this->hasMany(ZcRubricCriterio::class, 'rubric_id')->orderBy('orden');
    }

    public function getPuntajeTotalAttribute(): float
    {
        return $this->criterios->sum('puntaje_max');
    }
}
