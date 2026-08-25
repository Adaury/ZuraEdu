<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToTenant;
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'apellidos', 'cedula', 'email', 'password',
        'telefono', 'area_trabajo', 'activo',
        'pendiente_aprobacion', 'motivo_rechazo',
        'must_change_password', 'profile_photo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'      => 'datetime',
        'password'               => 'hashed',
        'activo'                 => 'boolean',
        'pendiente_aprobacion'   => 'boolean',
        'must_change_password'   => 'boolean',
    ];

    public function scopePendientes($q)
    {
        return $q->where('pendiente_aprobacion', true);
    }

    public function docente()
    {
        return $this->hasOne(Docente::class);
    }

    /** Los 4 roles cuyo alcance operativo es "docente" (mismo propósito, distinto dashboard/sidebar). */
    public const ROLES_DOCENTE = ['Docente', 'Docente Académico', 'Docente Técnico', 'Docente Guía'];

    /**
     * Determina si el usuario ejerce como docente, sin importar cuál de los
     * roles docentes tenga asignado. Usar en vez de hasRole('Docente'), que
     * solo matchea el rol genérico y deja fuera a las otras 3 variantes.
     * Requiere que ya exista un registro Docente vinculado (perfil completo).
     */
    public function esDocente(): bool
    {
        return $this->docente !== null;
    }

    /**
     * Tiene asignado alguno de los 4 roles docente, aunque todavía no haya
     * completado su perfil (registro Docente). Usar solo para detectar el
     * caso de onboarding incompleto — para autorización real usar esDocente().
     */
    public function tieneRolDocente(): bool
    {
        return $this->hasAnyRole(self::ROLES_DOCENTE);
    }

    public function nominaEmpleado()
    {
        return $this->hasOne(NominaEmpleado::class);
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->name . ' ' . $this->apellidos);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->profile_photo)) {
            return \Illuminate\Support\Facades\Storage::url($this->profile_photo);
        }
        return null;
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function casosAsignados()
    {
        return $this->hasMany(CasoSeguimiento::class, 'responsable_id');
    }
}
