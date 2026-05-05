<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tutoria extends Model
{
    use BelongsToTenant;

    protected $table = 'tutorias';

    protected $fillable = [
        'docente_id',
        'grupo_id',
        'school_year_id',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionTutoria::class)->orderBy('fecha', 'desc');
    }

    public function sesionesAsc(): HasMany
    {
        return $this->hasMany(SesionTutoria::class)->orderBy('fecha');
    }

    public function scopeActivas($q)
    {
        return $q->where('activo', true);
    }

    public function scopeDelAnio($q, int $yearId)
    {
        return $q->where('school_year_id', $yearId);
    }
}
