<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrumentoCriterio extends Model
{
    use BelongsToTenant;

    protected $table = 'instrumento_criterios';

    protected $fillable = [
        'instrumento_id', 'nombre', 'descripcion', 'orden', 'peso_max',
    ];

    protected $casts = [
        'peso_max' => 'decimal:2',
        'orden'    => 'integer',
    ];

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(InstrumentoEvaluacion::class, 'instrumento_id');
    }
}
