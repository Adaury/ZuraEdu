<?php

namespace App\Services;

use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use Illuminate\Support\Facades\Cache;

class CarnetRiskScoreService
{
    // Score 0–100 donde >70 = riesgo alto
    public static function calcular(CarnetIdentidad $carnet, int $diasAtras = 30): array
    {
        $cacheKey = "carnet_risk_{$carnet->tenant_id}_{$carnet->id}";

        return Cache::remember($cacheKey, 1800, function () use ($carnet, $diasAtras) {
            $desde = now()->subDays($diasAtras)->startOfDay();
            // Un carnet recién emitido no puede acumular ausencias de antes de existir.
            if ($carnet->created_at && $carnet->created_at->copy()->startOfDay()->gt($desde)) {
                $desde = $carnet->created_at->copy()->startOfDay();
            }

            $accesos = CarnetAcceso::withoutTenant()
                ->where('tenant_id', $carnet->tenant_id)
                ->where('carnet_identidad_id', $carnet->id)
                ->where('tipo_evento', 'entrada')
                ->where('created_at', '>=', $desde)
                ->orderBy('created_at')
                ->get()
                // Un día = una entrada (la primera): reescaneos fuera de la ventana de
                // dedupe inflaban "presencias" y escondían ausencias reales.
                ->unique(fn ($a) => $a->created_at->toDateString());

            // Días hábiles en el período (aproximado: excluye fines de semana)
            $diasHabiles = 0;
            for ($d = $desde->copy(); $d->lte(now()); $d->addDay()) {
                if ($d->dayOfWeek >= 1 && $d->dayOfWeek <= 5) $diasHabiles++;
            }

            $totalEntradas = $accesos->count();
            $tardanzas     = $accesos->where('estado', 'tardanza')->count();
            $ausencias      = max(0, $diasHabiles - $totalEntradas);

            // Fórmula: peso ausencias + peso tardanzas
            $scoreAusencias = ($diasHabiles > 0) ? ($ausencias / $diasHabiles) * 70 : 0;
            $scoreTardanzas = ($diasHabiles > 0) ? ($tardanzas / $diasHabiles) * 30 : 0;
            $score          = min(100, round($scoreAusencias + $scoreTardanzas));

            $nivel = match(true) {
                $score >= 70 => ['label' => 'Alto',   'color' => 'danger',  'icon' => 'bi-exclamation-triangle-fill'],
                $score >= 40 => ['label' => 'Medio',  'color' => 'warning', 'icon' => 'bi-exclamation-circle-fill'],
                default      => ['label' => 'Bajo',   'color' => 'success', 'icon' => 'bi-check-circle-fill'],
            };

            return [
                'score'        => $score,
                'nivel'        => $nivel,
                'ausencias'    => $ausencias,
                'tardanzas'    => $tardanzas,
                'presencias'   => $totalEntradas - $tardanzas,
                'dias_habiles' => $diasHabiles,
                'porcentaje_asistencia' => $diasHabiles > 0
                    ? round(($totalEntradas / $diasHabiles) * 100, 1)
                    : 0,
            ];
        });
    }

    public static function invalidar(CarnetIdentidad $carnet): void
    {
        Cache::forget("carnet_risk_{$carnet->tenant_id}_{$carnet->id}");
    }
}
