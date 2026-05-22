<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reconocimiento extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'estudiante_id',
        'tipo_id',
        'titulo',
        'descripcion',
        'fecha',
        'emitido_por_id',
        'entregado',
        'fecha_entrega',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'fecha_entrega' => 'date',
        'entregado'     => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoReconocimiento::class, 'tipo_id');
    }

    public function emitidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por_id')->withDefault(['name' => 'Sistema']);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeEntregados($q)
    {
        return $q->where('entregado', true);
    }

    public function scopePendientes($q)
    {
        return $q->where('entregado', false);
    }
}
