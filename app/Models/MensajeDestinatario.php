<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensajeDestinatario extends Model
{
    protected $fillable = [
        'mensaje_id',
        'destinatario_id',
        'leido_at',
        'archivado',
        'eliminado',
    ];

    protected $casts = [
        'leido_at'  => 'datetime',
        'archivado' => 'boolean',
        'eliminado' => 'boolean',
    ];

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class);
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }
}
