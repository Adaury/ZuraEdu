<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaltaDisciplinaria extends Model
{
    use BelongsToTenant;

    protected $table = 'faltas_disciplinarias';

    protected $fillable = [
        'estudiante_id',
        'docente_id',
        'tipo',
        'descripcion',
        'fecha',
        'resuelto',
        'notas_resolucion',
    ];

    protected $casts = [
        'fecha'    => 'date',
        'resuelto' => 'boolean',
    ];

    // ── Constantes ────────────────────────────────────────────────────────

    const TIPOS = [
        'tardanza'    => ['label' => 'Tardanza',      'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'bi-clock-history'],
        'falta_leve'  => ['label' => 'Falta Leve',    'color' => '#f97316', 'bg' => '#ffedd5', 'icon' => 'bi-exclamation-circle'],
        'falta_grave' => ['label' => 'Falta Grave',   'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'bi-exclamation-triangle'],
        'suspension'  => ['label' => 'Suspensión',    'color' => '#7c3aed', 'bg' => '#ede9fe', 'icon' => 'bi-slash-circle'],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class)->withDefault(['nombres' => '—', 'apellidos' => '']);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo] ?? ['label' => ucfirst($this->tipo), 'color' => '#6b7280', 'bg' => '#f1f5f9', 'icon' => 'bi-question-circle'];
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePendientes($query)
    {
        return $query->where('resuelto', false);
    }

    public function scopeResueltos($query)
    {
        return $query->where('resuelto', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDelEstudiante($query, int $estudianteId)
    {
        return $query->where('estudiante_id', $estudianteId);
    }
}
