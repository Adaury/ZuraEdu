<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ZcOpcion extends Model
{
    use BelongsToTenant;

    protected $table = 'zc_opciones';

    protected $fillable = ['pregunta_id', 'texto', 'es_correcta', 'orden'];

    protected $casts = ['es_correcta' => 'boolean'];

    public function pregunta()
    {
        return $this->belongsTo(ZcPregunta::class, 'pregunta_id');
    }
}
