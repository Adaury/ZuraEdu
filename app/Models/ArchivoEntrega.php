<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArchivoEntrega extends Model
{
    use BelongsToTenant;

    protected $table = 'archivos_entrega';

    protected $fillable = [
        'entrega_id',
        'nombre_original',
        'ruta',
        'tipo_mime',
        'tamanio',
    ];

    public function entrega()
    {
        return $this->belongsTo(EntregaClassroom::class, 'entrega_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }

    public function esImagen(): bool
    {
        return str_starts_with($this->tipo_mime, 'image/');
    }

    public function esPdf(): bool
    {
        return $this->tipo_mime === 'application/pdf';
    }

    public function getTamanioHumanoAttribute(): string
    {
        $bytes = $this->tamanio;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
