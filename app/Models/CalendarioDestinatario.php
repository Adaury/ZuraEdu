<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarioDestinatario extends Model
{
    use BelongsToTenant;

    protected $table = 'calendario_destinatarios';

    protected $fillable = ['tenant_id', 'calendario_id', 'user_id', 'notificado_at', 'correo_enviado_at'];

    protected $casts = [
        'notificado_at'     => 'datetime',
        'correo_enviado_at' => 'datetime',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(CalendarioAcademico::class, 'calendario_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
