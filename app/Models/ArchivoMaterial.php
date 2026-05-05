<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArchivoMaterial extends Model
{
    use BelongsToTenant;

    protected $table = 'archivos_material';

    protected $fillable = [
        'material_id',
        'nombre_original',
        'ruta',
        'tipo_mime',
    ];

    public function material()
    {
        return $this->belongsTo(MaterialClase::class, 'material_id');
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
}
