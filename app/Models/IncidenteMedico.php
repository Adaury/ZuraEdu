<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class IncidenteMedico extends Model
{
    use BelongsToTenant;

    protected $table = 'incidentes_medicos';

    protected $fillable = [
        'tenant_id',
        'estudiante_id',
        'fecha',
        'hora',
        'tipo',
        'descripcion',
        'accion_tomada',
        'remitido_a',
        'notificado_representante',
    ];

    protected $casts = [
        'fecha'                   => 'date',
        'notificado_representante' => 'boolean',
    ];

    // ── Constantes ────────────────────────────────────────────────────────

    const TIPOS = [
        'accidente'  => ['label' => 'Accidente',   'color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'bi-bandaid'],
        'enfermedad' => ['label' => 'Enfermedad',  'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'bi-thermometer-half'],
        'alergia'    => ['label' => 'Alergia',     'color' => '#7c3aed', 'bg' => '#ede9fe', 'icon' => 'bi-flower1'],
        'otro'       => ['label' => 'Otro',        'color' => '#0891b2', 'bg' => '#e0f2fe', 'icon' => 'bi-clipboard2-pulse'],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo] ?? ['label' => ucfirst($this->tipo), 'color' => '#6b7280', 'bg' => '#f1f5f9', 'icon' => 'bi-question'];
    }
}
