<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ComunicadoLectura extends Model
{
    use BelongsToTenant;

    protected $fillable = ['comunicado_id', 'user_id', 'leido_at', 'tenant_id'];

    protected $casts = ['leido_at' => 'datetime'];

    public function comunicado()
    {
        return $this->belongsTo(Comunicado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
