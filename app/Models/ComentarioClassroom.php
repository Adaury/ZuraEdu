<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ComentarioClassroom extends Model
{
    use BelongsToTenant;

    protected $table = 'comentarios_classroom';

    protected $fillable = [
        'material_id',
        'user_id',
        'contenido',
    ];

    public function material()
    {
        return $this->belongsTo(MaterialClase::class, 'material_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
