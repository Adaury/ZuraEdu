<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionAudit extends Model
{
    protected $table = 'calificacion_audits';

    protected $fillable = [
        'modelo',
        'registro_id',
        'matricula_id',
        'asignacion_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'user_id',
        'ip',
    ];

    protected $casts = [
        'valor_anterior' => 'float',
        'valor_nuevo'    => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra los campos numéricos que cambiaron entre el registro anterior y el nuevo.
     * Llama desde el controlador justo antes de updateOrCreate.
     */
    public static function registrarCambios(
        string $modelo,
        ?Model $anterior,
        array $nuevosDatos,
        int $matriculaId,
        int $asignacionId,
        array $camposVigilar
    ): void {
        $userId = auth()->id();
        $ip     = request()->ip();

        foreach ($camposVigilar as $campo) {
            $valNuevo = isset($nuevosDatos[$campo]) && $nuevosDatos[$campo] !== ''
                ? (float) $nuevosDatos[$campo]
                : null;

            $valAnterior = $anterior ? ($anterior->$campo !== null ? (float) $anterior->$campo : null) : null;

            if ($valAnterior === $valNuevo) {
                continue;
            }

            static::create([
                'modelo'        => $modelo,
                'registro_id'   => $anterior?->id ?? 0,
                'matricula_id'  => $matriculaId,
                'asignacion_id' => $asignacionId,
                'campo'         => $campo,
                'valor_anterior'=> $valAnterior,
                'valor_nuevo'   => $valNuevo,
                'user_id'       => $userId,
                'ip'            => $ip,
            ]);
        }
    }
}
