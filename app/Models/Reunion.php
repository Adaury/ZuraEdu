<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reunion extends Model
{
    use BelongsToTenant;

    protected $table = 'reuniones';

    protected $fillable = [
        'tenant_id',
        'titulo',
        'tipo',
        'fecha',
        'lugar',
        'convocante_id',
        'agenda',
        'participantes',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function convocante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'convocante_id');
    }

    public function acuerdos(): HasMany
    {
        return $this->hasMany(AcuerdoReunion::class, 'reunion_id')->orderBy('id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public static function tiposLabel(): array
    {
        return [
            'consejo_directivo' => 'Consejo Directivo',
            'reunion_padres'    => 'Reunión de Padres',
            'reunion_docentes'  => 'Reunión de Docentes',
            'comite'            => 'Comité',
            'otra'              => 'Otra',
        ];
    }

    public static function estadosLabel(): array
    {
        return [
            'programada' => 'Programada',
            'realizada'  => 'Realizada',
            'cancelada'  => 'Cancelada',
        ];
    }

    public function tipoLabel(): string
    {
        return self::tiposLabel()[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function estadoLabel(): string
    {
        return self::estadosLabel()[$this->estado] ?? ucfirst($this->estado);
    }

    /** Clases Bootstrap badge según estado */
    public function estadoBadgeClass(): string
    {
        return match ($this->estado) {
            'programada' => 'bg-primary',
            'realizada'  => 'bg-success',
            'cancelada'  => 'bg-danger',
            default      => 'bg-secondary',
        };
    }
}
