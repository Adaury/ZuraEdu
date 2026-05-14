<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SigerdConfig extends Model
{
    use BelongsToTenant;

    protected $table = 'sigerd_config';

    protected $fillable = [
        'tenant_id',
        'codigo_centro',
        'nombre_centro',
        'distrito',
        'regional',
        'modalidad',
        'sector',
        'anio_sigerd',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
