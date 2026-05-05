<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class Franja extends Model
{
    protected $table    = 'sch_franjas';
    protected $fillable = ['numero', 'hora_inicio', 'hora_fin', 'nombre', 'es_recreo', 'activa'];

    protected $casts = ['es_recreo' => 'boolean', 'activa' => 'boolean'];

    public function getLabelAttribute(): string
    {
        return $this->nombre ?? "{$this->hora_inicio} – {$this->hora_fin}";
    }
}
