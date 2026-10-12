<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Models\ActivityLog;
use App\Models\InsigniaEstudiante;
use App\Models\Matricula;
use App\Models\PuntoEstudiante;
use Illuminate\Console\Command;

/**
 * Antes de c4c3836 los puntos automáticos se deduplicaban por (concepto, fecha): generarlos otro día
 * volvía a sumar 50/40/20. Este comando deja UNA fila por (matrícula, concepto automático) — la más
 * antigua — y revoca las insignias por acumulado que ya no corresponden.
 *
 * Por defecto solo MUESTRA lo que haría (dry-run). Con --apply borra de verdad.
 */
class ConsolidarPuntosGamificacion extends Command
{
    use LoopsPerTenant;

    protected $signature   = 'gamificacion:consolidar-duplicados {--apply : Borrar los duplicados (sin esta opción solo se informa)}';
    protected $description = 'Quita los puntos automáticos duplicados de gamificación (deja el más antiguo por matrícula y concepto)';

    /** Conceptos que genera GamificacionController::generarPuntos(); los manuales no se tocan. */
    private const CONCEPTOS_AUTOMATICOS = [
        'Promedio académico ≥ 90',
        'Promedio académico ≥ 80',
        'Asistencia ≥ 95%',
        'Sin faltas disciplinarias',
    ];

    public function handle(): int
    {
        $aplicar = (bool) $this->option('apply');
        $this->line($aplicar ? 'MODO APLICAR: se borrarán los duplicados.' : 'Modo simulación (dry-run): no se borra nada. Usa --apply para aplicar.');

        $totalFilas = $totalPuntos = 0;

        $this->forEachTenant(function ($tenant) use ($aplicar, &$totalFilas, &$totalPuntos) {
            // Matricula está filtrada por tenant: así se acota el alcance (puntos_estudiante no tiene tenant_id).
            $matriculaIds = Matricula::pluck('id');
            $filasTenant = $puntosTenant = 0;

            foreach ($matriculaIds->chunk(500) as $chunk) {
                $filas = PuntoEstudiante::whereIn('matricula_id', $chunk)
                    ->whereIn('concepto', self::CONCEPTOS_AUTOMATICOS)
                    ->orderBy('fecha')->orderBy('id')
                    ->get(['id', 'matricula_id', 'concepto', 'puntos']);

                $afectadas = [];
                foreach ($filas->groupBy(fn ($f) => $f->matricula_id . '|' . $f->concepto) as $grupo) {
                    if ($grupo->count() < 2) {
                        continue;
                    }
                    $sobrantes = $grupo->slice(1);   // la primera (más antigua) se conserva
                    $filasTenant  += $sobrantes->count();
                    $puntosTenant += (int) $sobrantes->sum('puntos');
                    $afectadas[$grupo->first()->matricula_id] = true;

                    if ($aplicar) {
                        PuntoEstudiante::whereIn('id', $sobrantes->pluck('id'))->delete();
                    }
                }

                if ($aplicar) {
                    foreach (array_keys($afectadas) as $matriculaId) {
                        $total = (int) PuntoEstudiante::where('matricula_id', $matriculaId)->sum('puntos');
                        if ($total < 500) {
                            InsigniaEstudiante::where('matricula_id', $matriculaId)->where('tipo', 'quinientos_puntos')->delete();
                        }
                        if ($total < 100) {
                            InsigniaEstudiante::where('matricula_id', $matriculaId)->where('tipo', 'cien_puntos')->delete();
                        }
                    }
                }
            }

            $this->line("  [{$tenant->nombre_institucion}] {$filasTenant} fila(s) duplicada(s), {$puntosTenant} punto(s) de más");
            $totalFilas  += $filasTenant;
            $totalPuntos += $puntosTenant;

            if ($aplicar && $filasTenant > 0) {
                try {
                    ActivityLog::registrar(
                        'gamificacion.duplicados_consolidados',
                        PuntoEstudiante::class,
                        null,
                        "Consolidación de puntos automáticos duplicados: {$filasTenant} fila(s) eliminada(s), {$puntosTenant} punto(s)."
                    );
                } catch (\Throwable) {}
            }
        });

        $verbo = $aplicar ? 'eliminadas' : 'a eliminar';
        $this->info("Total: {$totalFilas} fila(s) {$verbo} ({$totalPuntos} puntos).");

        return self::SUCCESS;
    }
}
