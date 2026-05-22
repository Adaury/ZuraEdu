<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegranteProyecto extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'integrantes_proyecto';

    protected $fillable = [
        'tenant_id',
        'proyecto_id',
        'estudiante_id',
        'rol',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function proyecto()
    {
        return $this->belongsTo(ProyectoEscolar::class, 'proyecto_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class)->withDefault(['nombres' => '—', 'apellidos' => '']);
    }

    // ── Accesores ────────────────────────────────────────────────────────────

    public function getRolLabelAttribute(): string
    {
        return $this->rol === 'lider' ? 'Líder' : 'Integrante';
    }
}
