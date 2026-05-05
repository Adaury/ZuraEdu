<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mensaje extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'remitente_id', 'destinatario_id', 'asunto', 'cuerpo',
        'leido', 'leido_en', 'archivado_remitente', 'archivado_destinatario',
        'mensaje_padre_id',
    ];

    protected $casts = [
        'leido'                  => 'boolean',
        'leido_en'               => 'datetime',
        'archivado_remitente'    => 'boolean',
        'archivado_destinatario' => 'boolean',
    ];

    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'mensaje_padre_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'mensaje_padre_id')->latest();
    }

    public function scopeRecibidos($q, int $userId)
    {
        return $q->where('destinatario_id', $userId)->where('archivado_destinatario', false);
    }

    public function scopeEnviados($q, int $userId)
    {
        return $q->where('remitente_id', $userId)->where('archivado_remitente', false);
    }

    public function scopeNoLeidos($q)
    {
        return $q->where('leido', false);
    }
}
