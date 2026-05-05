<?php
namespace App\Services;

use App\Models\Calificacion;
use App\Models\ConfigCalificacion;

class CalificacionService
{
    /**
     * Calculate weighted final grade based on configurable component weights.
     */
    public static function calcularNota(array $componentes, int $schoolYearId): float
    {
        $pesos = ConfigCalificacion::getPesos($schoolYearId);

        $suma          = 0;
        $pesoAplicado  = 0;

        foreach (['tareas','practicas','participacion','proyecto','examen'] as $comp) {
            $valor = $componentes[$comp] ?? null;
            $peso  = $pesos[$comp] ?? 0;

            if (!is_null($valor) && $valor !== '' && isset($pesos[$comp])) {
                $suma         += (float)$valor * $peso;
                $pesoAplicado += $peso;
            }
        }

        if ($pesoAplicado <= 0) return 0;

        // Proportional redistribution when some components are null
        $nota = ($suma / $pesoAplicado) * ($pesoAplicado > 0 ? 1 : 0);

        if ($pesoAplicado < 100) {
            $nota = $suma / $pesoAplicado;
        } else {
            $nota = $suma / 100;
        }

        return round(min(100, max(0, $nota)), 2);
    }

    /**
     * Assign indicator level based on final grade.
     */
    public static function indicadorDesdeNota(float $nota): string
    {
        if ($nota >= 90) return 'Excelente';
        if ($nota >= 75) return 'Bueno';
        if ($nota >= 60) return 'En proceso';
        return 'Insuficiente';
    }

    /**
     * Get Bootstrap color class for a grade.
     */
    public static function colorNota(float $nota): string
    {
        if ($nota >= 90) return 'success';
        if ($nota >= 75) return 'primary';
        if ($nota >= 60) return 'warning';
        return 'danger';
    }
}
