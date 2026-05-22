<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Album extends Model
{
    use BelongsToTenant;

    protected $table = 'albumes';

    protected $fillable = [
        'tenant_id',
        'titulo',
        'descripcion',
        'portada',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
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
}
