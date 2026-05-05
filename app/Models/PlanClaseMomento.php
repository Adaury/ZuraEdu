<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanClaseMomento extends Model
{
    use BelongsToTenant;

    protected $table = 'plan_clase_momentos';

    protected $fillable = [
        'plan_clase_id', 'tipo', 'orden', 'duracion_minutos',
        'area_curricular', 'competencias_especificas', 'contenidos',
        'actividades', 'indicador_logro', 'recursos',
    ];

    protected $casts = [
        'duracion_minutos' => 'integer',
        'orden'            => 'integer',
    ];

    public static array $tipoLabels = [
        'inicio'     => 'Inicio',
        'desarrollo' => 'Desarrollo',
        'cierre'     => 'Cierre',
    ];

    public static array $tipoDuraciones = [
        'inicio'     => 10,
        'desarrollo' => 30,
        'cierre'     => 5,
    ];

    public static array $tipoColores = [
        'inicio'     => '#1d4ed8',
        'desarrollo' => '#065f46',
        'cierre'     => '#7c3aed',
    ];

    public function planClase(): BelongsTo
    {
        return $this->belongsTo(PlanClase::class);
    }

    public function getTipoLabelAttribute(): string
    {
        return self::$tipoLabels[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function getTipoColorAttribute(): string
    {
        return self::$tipoColores[$this->tipo] ?? '#1e3a6e';
    }
}
