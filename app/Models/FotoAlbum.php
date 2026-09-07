<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FotoAlbum extends Model
{
    use BelongsToTenant;

    protected $table = 'fotos_album';

    protected $fillable = [
        'tenant_id',
        'album_id',
        'ruta',
        'titulo',
        'orden',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * Ruta relativa (no absoluta): el sitio público se sirve por subdominio
     * por tenant y Storage::url() fija el host de APP_URL, que no siempre
     * coincide con el dominio real desde el que se accede a la imagen.
     */
    public function getUrlAttribute(): string
    {
        return '/storage/' . $this->ruta;
    }

    // ── Boot ──────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (FotoAlbum $foto) {
            if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
                Storage::disk('public')->delete($foto->ruta);
            }
        });
    }
}
