<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $table = 'support_messages';

    protected $fillable = [
        'session_id', 'mensaje', 'origen', 'user_id', 'leido',
    ];

    protected $casts = [
        'leido' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function session()
    {
        return $this->belongsTo(SupportSession::class, 'session_id');
    }

    public function agente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function toChat(): array
    {
        return [
            'id'         => $this->id,
            'mensaje'    => $this->mensaje,
            'origen'     => $this->origen,
            'user_name'  => $this->origen === 'admin'
                            ? ($this->agente?->name ?? 'Soporte')
                            : $this->session?->visitor_nombre,
            'hora'       => $this->created_at->format('H:i'),
            'tiempo'     => $this->created_at->diffForHumans(),
        ];
    }
}
