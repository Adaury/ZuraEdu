<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervencionCaso extends Model
{
    use BelongsToTenant;

    protected $table = 'intervenciones_caso';

    protected $fillable = [
        'tenant_id',
        'caso_id',
        'descripcion',
        'tipo_intervencion',
        'fecha',
        'resultado',
        'siguiente_accion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // ── Catálogos ────────────────────────────────────────────────────────────

    const TIPOS = [
        'reunion'    => ['label' => 'Reunión',    'icon' => '👥'],
        'llamada'    => ['label' => 'Llamada',    'icon' => '📞'],
        'visita'     => ['label' => 'Visita',     'icon' => '🏠'],
        'derivacion' => ['label' => 'Derivación', 'icon' => '↗️'],
        'otro'       => ['label' => 'Otro',       'icon' => '📝'],
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function caso(): BelongsTo
    {
        return $this->belongsTo(CasoSeguimiento::class, 'caso_id');
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo_intervencion] ?? ['label' => ucfirst($this->tipo_intervencion), 'icon' => '📝'];
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo_intervencion]['label'] ?? ucfirst($this->tipo_intervencion);
    }
}
