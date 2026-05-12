<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QrAsistenciaToken extends Model
{
    use BelongsToTenant;

    protected $table = 'qr_asistencia_tokens';

    protected $fillable = [
        'tenant_id', 'token', 'asignacion_id', 'docente_id',
        'fecha', 'expires_at', 'activo', 'duracion_minutos',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'expires_at' => 'datetime',
        'activo'     => 'boolean',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function isValido(): bool
    {
        return $this->activo && $this->expires_at->isFuture();
    }

    public function segundosRestantes(): int
    {
        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }
}
