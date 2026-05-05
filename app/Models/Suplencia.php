<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Suplencia extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'horario_detalle_id',
        'docente_original_id',
        'docente_suplente_id',
        'fecha',
        'estado',
        'motivo',
        'notas_suplente',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function detalle()
    {
        return $this->belongsTo(HorarioDetalle::class, 'horario_detalle_id');
    }

    public function docenteOriginal()
    {
        return $this->belongsTo(Docente::class, 'docente_original_id');
    }

    public function docenteSuplente()
    {
        return $this->belongsTo(Docente::class, 'docente_suplente_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
