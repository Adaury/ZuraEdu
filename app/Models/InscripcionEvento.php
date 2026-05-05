<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InscripcionEvento extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'inscripciones_evento';

    protected $fillable = [
        'evento_id',
        'estudiante_id',
        'fecha_inscripcion',
        'asistio',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'date',
        'asistio'           => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
