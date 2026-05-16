<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaTecnica extends Model
{
    use BelongsToTenant;

    protected $table = 'areas_tecnicas';

    protected $fillable = [
        'nombre', 'codigo', 'descripcion', 'color', 'activo', 'orden',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function cursos(): HasMany
    {
        return $this->hasMany(CursoTecnico::class)->orderBy('orden')->orderBy('nombre');
    }

    public function scopeActivas($q) { return $q->where('activo', true); }
}
