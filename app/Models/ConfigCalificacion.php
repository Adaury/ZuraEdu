<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ConfigCalificacion extends Model
{
    use BelongsToTenant;

    protected $table = 'config_calificaciones';

    protected $fillable = [
        'school_year_id',
        'componente',
        'peso',
        'activo',
    ];

    protected $casts = [
        'peso'   => 'float',
        'activo' => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public static function getPesos(int $schoolYearId)
    {
        return static::where('school_year_id', $schoolYearId)
            ->where('activo', true)
            ->get()
            ->keyBy('componente');
    }
}
