<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapacitacionVista extends Model
{
    use BelongsToTenant;

    protected $table = 'capacitaciones_vistas';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'contenido_id',
        'visto_at',
    ];

    protected $casts = [
        'visto_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
