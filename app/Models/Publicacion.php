<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Publicacion extends Model
{
    use BelongsToTenant;

    protected $table = 'publicaciones';

    protected $fillable = [
        'tenant_id', 'tipo', 'titulo', 'contenido', 'imagen_destacada',
        'fecha', 'estado', 'visible', 'creado_por',
    ];

    protected $casts = [
        'fecha'   => 'date',
        'visible' => 'boolean',
    ];

    public const TIPOS = [
        'noticia'      => 'Noticia',
        'aviso'        => 'Aviso',
        'comunicado'   => 'Comunicado',
        'actividad'    => 'Actividad',
        'logro'        => 'Logro',
        'convocatoria' => 'Convocatoria',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen_destacada ? Storage::disk('public')->url($this->imagen_destacada) : null;
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /** Lo que un visitante anónimo del sitio público puede ver. */
    public function scopeVisiblesPublico(Builder $q): Builder
    {
        return $q->where('estado', 'publicado')
            ->where('visible', true)
            ->where('fecha', '<=', now()->toDateString());
    }

    public function scopeRecientes(Builder $q): Builder
    {
        return $q->orderByDesc('fecha')->orderByDesc('id');
    }
}
