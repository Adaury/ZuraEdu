<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Album extends Model
{
    use BelongsToTenant;

    /** Mínimo de fotos para que un álbum pueda usarse como carrusel del sitio público. */
    public const MIN_FOTOS_CARRUSEL = 5;

    protected $table = 'albumes';

    protected $fillable = [
        'tenant_id',
        'titulo',
        'descripcion',
        'portada',
        'activo',
        'orden',
        'mostrar_en_sitio',
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'mostrar_en_sitio' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoAlbum::class, 'album_id')->orderBy('orden');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getPortadaUrlAttribute(): string
    {
        if ($this->portada) {
            return Storage::disk('public')->url($this->portada);
        }

        // Usar la primera foto como portada si no hay portada asignada
        $primera = $this->fotos()->first();
        if ($primera) {
            return Storage::disk('public')->url($primera->ruta);
        }

        return '';
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('created_at', 'desc');
    }

    /** El único álbum (si existe) marcado para mostrarse como carrusel en /sitio. */
    public function scopeCarruselDelSitio($query)
    {
        return $query->where('mostrar_en_sitio', true)->where('activo', true);
    }
}
