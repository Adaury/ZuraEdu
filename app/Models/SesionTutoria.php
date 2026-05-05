<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionTutoria extends Model
{
    use BelongsToTenant;

    protected $table = 'sesiones_tutoria';

    protected $fillable = [
        'tutoria_id',
        'fecha',
        'tema',
        'descripcion',
        'estudiantes_atendidos',
        'acuerdos',
        'proxima_sesion',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'proxima_sesion'  => 'date',
    ];

    public function tutoria(): BelongsTo
    {
        return $this->belongsTo(Tutoria::class);
    }
}
