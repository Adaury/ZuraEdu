<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class FichaSalud extends Model
{
    use BelongsToTenant;

    protected $table = 'fichas_salud';

    protected $fillable = [
        'estudiante_id',
        'tipo_sangre',
        'alergias',
        'condiciones_medicas',
        'medicamentos',
        'contacto_emergencia',
        'telefono_emergencia',
        'seguro_medico',
        'num_seguro',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    // ── Tipos de sangre válidos ───────────────────────────────────────────

    public const TIPOS_SANGRE = ['A+', 'A−', 'B+', 'B−', 'AB+', 'AB−', 'O+', 'O−'];
}
