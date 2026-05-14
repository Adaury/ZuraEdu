<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantChatMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'mensaje', 'tipo',
        'archivo_url', 'archivo_nombre', 'leido_por_todos',
    ];

    protected $casts = [
        'leido_por_todos' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toChat(): array
    {
        return [
            'id'             => $this->id,
            'mensaje'        => $this->mensaje,
            'tipo'           => $this->tipo,
            'archivo_url'    => $this->archivo_url,
            'archivo_nombre' => $this->archivo_nombre,
            'user_id'        => $this->user_id,
            'user_name'      => $this->user?->name ?? 'Usuario',
            'user_avatar'    => $this->user?->profile_photo_url ?? null,
            'tiempo'         => $this->created_at?->diffForHumans() ?? 'ahora',
            'hora'           => $this->created_at?->format('H:i') ?? '',
        ];
    }
}
