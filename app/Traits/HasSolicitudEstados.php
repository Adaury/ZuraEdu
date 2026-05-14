<?php

namespace App\Traits;

trait HasSolicitudEstados
{
    // Método estático en lugar de constante (PHP < 8.2 no admite const en traits)
    public static function estados(): array
    {
        return [
            'pendiente'  => ['label' => 'Pendiente',  'color' => '#d97706', 'bg' => '#fffbeb'],
            'en_proceso' => ['label' => 'En Proceso', 'color' => '#2563eb', 'bg' => '#eff6ff'],
            'aprobada'   => ['label' => 'Aprobada',   'color' => '#16a34a', 'bg' => '#f0fdf4'],
            'rechazada'  => ['label' => 'Rechazada',  'color' => '#dc2626', 'bg' => '#fef2f2'],
        ];
    }

    public function getEstadoConfigAttribute(): array
    {
        return static::estados()[$this->estado] ?? static::estados()['pendiente'];
    }

    public function getTipoLabelAttribute(): string
    {
        return static::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }
}
