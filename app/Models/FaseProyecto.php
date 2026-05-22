<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaseProyecto extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'fases_proyecto';

    protected $fillable = [
        'tenant_id',
        'proyecto_id',
        'nombre',
        'descripcion',
        'fecha_limite',
        'completada',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
        'completada'   => 'boolean',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function proyecto()
    {
        return $this->belongsTo(ProyectoEscolar::class, 'proyecto_id');
    }

    // ── Accesores ────────────────────────────────────────────────────────────

    public function getVencidaAttribute(): bool
    {
        return !$this->completada && $this->fecha_limite->isPast();
    }
}
