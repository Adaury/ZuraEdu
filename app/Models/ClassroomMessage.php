<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomMessage extends Model
{
    use BelongsToTenant;

    protected $table = 'classroom_messages';

    protected $fillable = [
        'tenant_id', 'clase_virtual_id', 'user_id',
        'receptor_id', 'mensaje', 'tipo', 'fijado',
    ];

    protected $casts = [
        'fijado' => 'boolean',
    ];

    public function claseVirtual(): BelongsTo
    {
        return $this->belongsTo(ClaseVirtual::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    public function scopeGenerales($q)
    {
        return $q->where('tipo', 'general');
    }

    public function scopePrivadosEntre($q, int $userA, int $userB)
    {
        return $q->where('tipo', 'privado')
            ->where(fn($s) =>
                $s->where(fn($x) => $x->where('user_id', $userA)->where('receptor_id', $userB))
                  ->orWhere(fn($x) => $x->where('user_id', $userB)->where('receptor_id', $userA))
            );
    }
}
