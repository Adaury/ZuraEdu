<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PlanifAnual extends Model
{
    use BelongsToTenant;

    protected $table    = 'planif_anuales';
    protected $fillable = [
        'tenant_id', 'docente_id', 'asignacion_id', 'school_year_id',
        'titulo', 'descripcion',
    ];

    public function unidades()  { return $this->hasMany(PlanifUnidad::class)->orderBy('numero'); }
    public function asignacion(){ return $this->belongsTo(Asignacion::class); }
    public function docente()   { return $this->belongsTo(Docente::class); }
    public function schoolYear(){ return $this->belongsTo(SchoolYear::class); }
}
