<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    protected $table = 'observaciones';

    protected $fillable = [
        'docente_id', 'estudiante_id', 'asignacion_id', 'periodo_id',
        'tipo', 'texto', 'privada',
    ];

    protected $casts = [
        'privada' => 'boolean',
    ];

    const TIPOS = [
        'academica'  => ['label' => 'Académica',   'color' => '#3b82f6', 'icon' => 'bi-book'],
        'conductual' => ['label' => 'Conductual',  'color' => '#ef4444', 'icon' => 'bi-person-exclamation'],
        'positiva'   => ['label' => 'Positiva',    'color' => '#10b981', 'icon' => 'bi-star'],
        'general'    => ['label' => 'General',     'color' => '#6b7280', 'icon' => 'bi-chat-square-text'],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function docente()     { return $this->belongsTo(Docente::class); }
    public function estudiante()  { return $this->belongsTo(Estudiante::class); }
    public function asignacion()  { return $this->belongsTo(Asignacion::class); }
    public function periodo()     { return $this->belongsTo(Periodo::class); }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo] ?? self::TIPOS['general'];
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopePublicas($query)
    {
        return $query->where('privada', false);
    }

    public function scopeDelEstudiante($query, int $estudianteId)
    {
        return $query->where('estudiante_id', $estudianteId);
    }
}
