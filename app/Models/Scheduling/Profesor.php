<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    protected $table    = 'sch_profesores';
    protected $fillable = ['nombre', 'apellidos', 'email', 'especialidad', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'profesor_id');
    }

    public function disponibilidad()
    {
        return $this->hasMany(DisponibilidadProfesor::class, 'profesor_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellidos}, {$this->nombre}";
    }

    // Verifica si el profesor está disponible en día + franja
    public function estaDisponible(string $dia, int $franjaId): bool
    {
        $disp = $this->disponibilidad
            ->where('dia', $dia)
            ->where('franja_id', $franjaId)
            ->first();

        // Sin registro = disponible por defecto
        return $disp === null || $disp->disponible;
    }
}
