<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class AvisoEmergencia extends Model
{
    use BelongsToTenant;

    protected $table = 'avisos_emergencia';

    protected $fillable = [
        'titulo',
        'mensaje',
        'tipo',
        'enviado_por_id',
        'destinatarios',
        'grupo_id',
        'total_enviados',
    ];

    // ── Etiquetas y colores por tipo ──────────────────────────────────────

    const TIPOS = [
        'emergencia'  => 'Emergencia',
        'suspension'  => 'Suspensión',
        'actividad'   => 'Actividad',
        'informativo' => 'Informativo',
    ];

    const BADGE_CLASES = [
        'emergencia'  => 'bg-red-100 text-red-800 border border-red-300',
        'suspension'  => 'bg-orange-100 text-orange-800 border border-orange-300',
        'actividad'   => 'bg-blue-100 text-blue-800 border border-blue-300',
        'informativo' => 'bg-gray-100 text-gray-700 border border-gray-300',
    ];

    const ICONOS = [
        'emergencia'  => 'bi-exclamation-octagon-fill',
        'suspension'  => 'bi-calendar-x-fill',
        'actividad'   => 'bi-calendar-event-fill',
        'informativo' => 'bi-info-circle-fill',
    ];

    const DESTINATARIOS_LABELS = [
        'todos'    => 'Todos',
        'padres'   => 'Representantes',
        'docentes' => 'Docentes',
        'grupo'    => 'Grupo específico',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function enviadoPor()
    {
        return $this->belongsTo(User::class, 'enviado_por_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getBadgeClaseAttribute(): string
    {
        return self::BADGE_CLASES[$this->tipo] ?? 'bg-gray-100 text-gray-700';
    }

    public function getIconoAttribute(): string
    {
        return self::ICONOS[$this->tipo] ?? 'bi-bell-fill';
    }

    public function getDestinatariosLabelAttribute(): string
    {
        $label = self::DESTINATARIOS_LABELS[$this->destinatarios] ?? $this->destinatarios;

        if ($this->destinatarios === 'grupo' && $this->grupo) {
            $label .= ': ' . $this->grupo->nombre_completo;
        }

        return $label;
    }
}
