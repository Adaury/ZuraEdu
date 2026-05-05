<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'accion',
        'modelo',
        'modelo_id',
        'descripcion',
        'ip',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function registrar(
        string $accion,
        ?string $modelo = null,
        ?int $modeloId = null,
        ?string $descripcion = null
    ): self {
        return static::create([
            'user_id'     => auth()->id(),
            'accion'      => $accion,
            'modelo'      => $modelo,
            'modelo_id'   => $modeloId,
            'descripcion' => $descripcion,
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
