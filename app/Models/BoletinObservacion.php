<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletinObservacion extends Model
{
    use BelongsToTenant;

    protected $table = 'boletin_observaciones';

    protected $fillable = [
        'matricula_id', 'school_year_id', 'periodo_id',
        'tipo', 'contenido', 'docente_id',
    ];

    public function matricula(): BelongsTo  { return $this->belongsTo(Matricula::class); }
    public function schoolYear(): BelongsTo { return $this->belongsTo(SchoolYear::class); }
    public function periodo(): BelongsTo    { return $this->belongsTo(Periodo::class); }
    public function docente(): BelongsTo    { return $this->belongsTo(Docente::class); }

    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'academica'  => 'Académica',
            'conducta'   => 'Conducta',
            'sugerencia' => 'Sugerencia',
            default      => 'General',
        };
    }
}
