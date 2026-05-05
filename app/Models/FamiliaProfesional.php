<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamiliaProfesional extends Model
{
    protected $table = 'familias_profesionales';

    protected $fillable = [
        'nombre', 'descripcion', 'color', 'icono', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function asignaturas(): HasMany
    {
        return $this->hasMany(Asignatura::class, 'familia_id')->orderBy('nombre');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
