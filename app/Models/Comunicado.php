<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Comunicado extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'titulo', 'cuerpo', 'autor_id', 'tipo_destinatarios',
        'grupo_id', 'published_at', 'activo', 'es_interno',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'activo'       => 'boolean',
        'es_interno'   => 'boolean',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function lecturas()
    {
        return $this->hasMany(ComunicadoLectura::class);
    }

    public function fueLeídoPor(int $userId): bool
    {
        return $this->lecturas()->where('user_id', $userId)->exists();
    }

    public function marcarLeido(int $userId): void
    {
        $this->lecturas()->firstOrCreate(
            ['user_id' => $userId],
            ['leido_at' => now(), 'tenant_id' => tenant_id() ?? 0]
        );
    }

    public function scopeInternos(Builder $q): Builder
    {
        return $q->where('es_interno', true);
    }

    public function scopeParaStaff(Builder $q, \App\Models\User $user): Builder
    {
        $tipos = ['todos'];

        if ($user->tieneRolDocente())                                                           $tipos[] = 'docentes';
        if ($user->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo',
                               'Coordinador Segundo Ciclo','Director','Administrador'])) {
            $tipos[] = 'coordinadores';
            $tipos[] = 'docentes';
        }

        return $q->whereIn('tipo_destinatarios', $tipos);
    }

    /**
     * Comunicados internos publicados que el usuario ve como personal y aún no ha acusado (caché 2 min).
     * Un solo punto para el contador de las barras laterales (antes había 4 copias, todas con el rol
     * «Docente» literal que dejaba fuera a Docente Académico/Técnico/Guía). La clave de caché la invalida
     * PortalDocenteController::comintMarcarLeido y la API móvil al registrar el acuse.
     */
    public static function internosSinLeer(\App\Models\User $user): int
    {
        return (int) \Illuminate\Support\Facades\Cache::remember(
            't' . (tenant_id() ?? 0) . '_user_' . $user->id . '_comint_unread',
            120,
            fn () => static::internos()->publicados()->paraStaff($user)
                ->whereDoesntHave('lecturas', fn ($q) => $q->where('user_id', $user->id))
                ->count()
        );
    }

    public function scopePublicados(Builder $q): Builder
    {
        return $q->whereNotNull('published_at')
                 ->where('published_at', '<=', now())
                 ->where('activo', true);
    }

    public function scopeParaUsuario(Builder $q, \App\Models\User $user): Builder
    {
        $tipos = ['todos'];

        if ($user->tieneRolDocente())             $tipos[] = 'docentes';
        if ($user->hasAnyRole(['Coordinador', 'Director', 'Administrador'])) {
            $tipos[] = 'coordinadores';
            $tipos[] = 'docentes';
        }

        // Group-specific for students (via matricula)
        $grupoIds = Matricula::where('estado', 'activa')
            ->whereHas('estudiante', fn($sq) => $sq->where('user_id', $user->id))
            ->pluck('grupo_id');

        return $q->where(function ($inner) use ($tipos, $grupoIds) {
            $inner->whereIn('tipo_destinatarios', $tipos)
                  ->orWhere(function ($grp) use ($grupoIds) {
                      $grp->where('tipo_destinatarios', 'grupo')
                          ->whereIn('grupo_id', $grupoIds);
                  });
        });
    }

    public function getEsPublicadoAttribute(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast() && $this->activo;
    }
}
