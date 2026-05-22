<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Planificacion extends Model
{
    use BelongsToTenant;

    protected $table = 'planificaciones';

    protected $fillable = [
        'tenant_id',
        'asignacion_id', 'school_year_id', 'tipo',
        'familia_profesional', 'denominacion', 'modulo_nombre',
        'mf_codigo', 'uc_codigo', 'sesion', 'nivel',
        'horas', 'fecha_inicio', 'fecha_fin',
        'publicado', 'creado_por',
    ];

    protected $casts = [
        'publicado'    => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'horas'        => 'float',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function raItems()
    {
        return $this->hasMany(PlanificacionRaItem::class)->orderBy('orden');
    }

    public function actividades()
    {
        return $this->hasMany(PlanificacionActividad::class)->orderBy('actividad_numero');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePorTipo($q, string $tipo)
    {
        return $q->where('tipo', $tipo);
    }

    public function scopePublicadas($q)
    {
        return $q->where('publicado', true);
    }
}
