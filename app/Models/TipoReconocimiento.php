<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoReconocimiento extends Model
{
    use BelongsToTenant;

    protected $table = 'tipos_reconocimiento';

    protected $fillable = [
        'tenant_id',
        'nombre',
        'descripcion',
        'icono',
        'color',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function reconocimientos(): HasMany
    {
        return $this->hasMany(Reconocimiento::class, 'tipo_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Color Tailwind normalizado para badge */
    public function badgeClasses(): string
    {
        $map = [
            'bg-yellow-400' => 'bg-yellow-100 text-yellow-800 ring-yellow-300',
            'bg-blue-400'   => 'bg-blue-100 text-blue-800 ring-blue-300',
            'bg-purple-400' => 'bg-purple-100 text-purple-800 ring-purple-300',
            'bg-green-400'  => 'bg-green-100 text-green-800 ring-green-300',
            'bg-rose-400'   => 'bg-rose-100 text-rose-800 ring-rose-300',
        ];

        return $map[$this->color] ?? 'bg-gray-100 text-gray-700 ring-gray-300';
    }
}
