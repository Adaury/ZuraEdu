<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcuerdoReunion extends Model
{
    use BelongsToTenant;

    protected $table = 'acuerdos_reunion';

    protected $fillable = [
        'tenant_id',
        'reunion_id',
        'descripcion',
        'responsable',
        'fecha_limite',
        'cumplido',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
        'cumplido'     => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }
}
