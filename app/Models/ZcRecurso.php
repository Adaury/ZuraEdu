<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ZcRecurso extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_recursos';

    protected $fillable = [
        'clase_virtual_id',
        'creado_por',
        'titulo',
        'tipo',
        'descripcion',
        'url',
        'ruta_archivo',
        'nombre_archivo',
        'orden',
        'publico',
    ];

    protected $casts = ['publico' => 'boolean'];

    const TIPOS = [
        'pdf'          => ['label' => 'PDF',           'icon' => 'bi-file-pdf-fill',       'color' => '#EF4444'],
        'video'        => ['label' => 'Video',          'icon' => 'bi-play-circle-fill',    'color' => '#8B5CF6'],
        'enlace'       => ['label' => 'Enlace',         'icon' => 'bi-link-45deg',          'color' => '#3B82F6'],
        'imagen'       => ['label' => 'Imagen',         'icon' => 'bi-image-fill',          'color' => '#10B981'],
        'presentacion' => ['label' => 'Presentación',   'icon' => 'bi-easel-fill',          'color' => '#F59E0B'],
        'otro'         => ['label' => 'Otro',           'icon' => 'bi-file-earmark-fill',   'color' => '#6B7280'],
    ];

    public function claseVirtual()
    {
        return $this->belongsTo(ClaseVirtual::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo] ?? self::TIPOS['otro'];
    }

    public function getUrlArchivoAttribute(): ?string
    {
        return $this->ruta_archivo
            ? Storage::disk('public')->url($this->ruta_archivo)
            : null;
    }

    public function getEnlaceAttribute(): string
    {
        return $this->url ?? $this->url_archivo ?? '#';
    }
}
