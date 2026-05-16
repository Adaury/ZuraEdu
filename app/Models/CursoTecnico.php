<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CursoTecnico extends Model
{
    use BelongsToTenant;

    protected $table = 'cursos_tecnicos';

    protected $fillable = [
        'area_tecnica_id', 'nombre', 'codigo', 'descripcion',
        'duracion_horas', 'activo', 'orden',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(AreaTecnica::class, 'area_tecnica_id');
    }

    public function modulos(): HasMany
    {
        return $this->hasMany(ModuloFormativo::class)->orderBy('orden')->orderBy('nombre');
    }

    public function scopeActivos($q) { return $q->where('activo', true); }
}
