<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Representante extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'cedula', 'nombres', 'apellidos',
        'telefono', 'telefono_trabajo', 'email',
        'ocupacion', 'direccion', 'foto',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_representante')
            ->withPivot('parentesco', 'es_principal')
            ->withTimestamps();
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function getFotoUrlAttribute(): string
    {
        return $this->foto
            ? asset('storage/' . $this->foto)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->nombre_completo) . '&background=2563eb&color=fff&size=128';
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeActivos($query)
    {
        return $query->whereHas('user', fn($q) => $q->where('activo', true));
    }
}
