<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParadaRuta extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'paradas_ruta';

    protected $fillable = [
        'tenant_id',
        'ruta_id',
        'nombre',
        'orden',
        'hora_estimada',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function ruta()
    {
        return $this->belongsTo(RutaTransporte::class, 'ruta_id');
    }

    public function estudiantesRuta()
    {
        return $this->hasMany(EstudianteRuta::class, 'parada_id');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiantes_ruta', 'parada_id', 'estudiante_id')
                    ->withPivot('tipo')
                    ->withTimestamps();
    }
}
