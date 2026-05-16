<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuloFormativo extends Model
{
    use BelongsToTenant;

    protected $table = 'modulos_formativos';

    protected $fillable = [
        'curso_tecnico_id', 'nombre', 'codigo', 'descripcion',
        'duracion_horas', 'creditos', 'orden', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(CursoTecnico::class, 'curso_tecnico_id');
    }

    public function scopeActivos($q) { return $q->where('activo', true); }
}
