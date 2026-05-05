<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    use BelongsToTenant;

    protected $table    = 'secciones';
    protected $fillable = ['nombre', 'orden'];

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function scopeOrdenadas($q)
    {
        return $q->orderBy('orden');
    }
}
