<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Comunicado extends Model
{
    protected $fillable = [
        'titulo', 'cuerpo', 'autor_id', 'tipo_destinatarios',
        'grupo_id', 'published_at', 'activo',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'activo'       => 'boolean',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
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

        if ($user->hasRole('Docente'))      $tipos[] = 'docentes';
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
