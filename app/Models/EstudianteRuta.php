<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EstudianteRuta extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'estudiantes_ruta';

    protected $fillable = [
        'tenant_id',
        'ruta_id',
        'estudiante_id',
        'tipo',
        'parada_id',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function ruta()
    {
        return $this->belongsTo(RutaTransporte::class, 'ruta_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function parada()
    {
        return $this->belongsTo(ParadaRuta::class, 'parada_id');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'ida'    => 'Solo ida',
            'vuelta' => 'Solo vuelta',
            'ambos'  => 'Ida y vuelta',
            default  => $this->tipo,
        };
    }
}
