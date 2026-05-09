<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class PreMatricula extends Model
{
    use BelongsToTenant;

    protected $table = 'pre_matriculas';

    protected $fillable = [
        'codigo',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'genero',
        'lugar_nacimiento',
        'cedula_estudiante',
        'grado_solicitado',
        'nombre_representante',
        'cedula_representante',
        'relacion_representante',
        'telefono',
        'email',
        'direccion',
        'estado',
        'notas_admin',
        'documentos',
        'estudiante_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'documentos'       => 'array',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'aprobada'  => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800"><i class="bi bi-check-circle-fill"></i> Aprobada</span>',
            'rechazada' => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800"><i class="bi bi-x-circle-fill"></i> Rechazada</span>',
            default     => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800"><i class="bi bi-clock-fill"></i> Pendiente</span>',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePorGrado($query, string $grado)
    {
        return $query->where('grado_solicitado', $grado);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generateCodigo(): string
    {
        do {
            $codigo = 'PM-' . strtoupper(substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 8));
        } while (static::withoutGlobalScopes()->where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function estaConvertida(): bool
    {
        return ! is_null($this->estudiante_id);
    }

    public static function gradosDisponibles(): array
    {
        return [
            '1ro Primaria',
            '2do Primaria',
            '3ro Primaria',
            '4to Primaria',
            '5to Primaria',
            '6to Primaria',
            '1ro Secundaria',
            '2do Secundaria',
            '3ro Secundaria',
            '4to Secundaria',
            '5to Secundaria',
            '6to Secundaria',
        ];
    }
}
