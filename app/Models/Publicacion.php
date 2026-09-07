<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Publicacion extends Model
{
    use BelongsToTenant;

    protected $table = 'publicaciones';

    protected $fillable = [
        'tenant_id', 'tipo', 'titulo', 'contenido', 'imagen_destacada',
        'imagen_alineacion', 'fecha', 'estado', 'visible', 'creado_por',
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

    public const ALINEACIONES = ['izquierda', 'centro', 'derecha'];

    public const RESUMEN_LARGO = 150;

    // ── Relaciones ────────────────────────────────────────────────────────

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * Ruta relativa (no absoluta): el sitio público se sirve por subdominio
     * por tenant y Storage::url() fija el host de APP_URL, que no siempre
     * coincide con el dominio real desde el que se accede a la imagen.
     */
    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen_destacada ? '/storage/' . $this->imagen_destacada : null;
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /** Reseña corta sin HTML para la tarjeta pública — mismo patrón que el email de Comunicado. */
    public function getResumenAttribute(): string
    {
        return Str::limit(trim(strip_tags($this->contenido)), self::RESUMEN_LARGO);
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
