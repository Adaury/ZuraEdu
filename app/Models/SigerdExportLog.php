<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SigerdExportLog extends Model
{
    use BelongsToTenant;

    protected $table = 'sigerd_export_logs';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'tipo',
        'grupo_id',
        'school_year_id',
        'periodo_id',
        'formato',
        'total_registros',
        'errores_validacion',
        'archivo_nombre',
        'notas',
        'created_at',
    ];

    protected $casts = [
        'errores_validacion' => 'array',
        'created_at'         => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }
}
