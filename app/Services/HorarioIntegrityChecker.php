<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\HorarioDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Verificador de integridad post-generación.
 *
 * Corre 4 checks sobre un horario ya persistido en BD:
 *
 *   I1 — Sin docente duplicado en el mismo día + franja
 *   I2 — Sin aula duplicada en el mismo día + franja
 *   I3 — Sin grupo duplicado en el mismo día + franja
 *   I4 — Cada asignación tiene al menos las horas_semana requeridas
 *
 * Retorna un array con el listado de violaciones, sin lanzar excepciones.
 */
class HorarioIntegrityChecker
{
    // =========================================================================
    //  API PÚBLICA
    // =========================================================================

    /**
     * @return array{
     *   valido: bool,
     *   violaciones: array<array{tipo: string, severidad: string, mensaje: string}>,
     *   total_detalles: int,
     *   checks_pasados: int,
     *   checks_total: int
     * }
     */
    public function verificar(Horario $horario): array
    {
        $detalles = HorarioDetalle::with([
            'asignacion.docente',
            'asignacion.grupo',
            'asignacion.asignatura',
            'aula',
            'franja',
        ])->where('horario_id', $horario->id)->get();

        $violaciones = [];
        $checksOk    = 0;
        $total       = 4;

        $v = $this->checkDocenteDuplicado($detalles);
        if (empty($v)) { $checksOk++; }
        $violaciones = array_merge($violaciones, $v);

        $v = $this->checkAulaDuplicada($detalles);
        if (empty($v)) { $checksOk++; }
        $violaciones = array_merge($violaciones, $v);

        $v = $this->checkGrupoDuplicado($detalles);
        if (empty($v)) { $checksOk++; }
        $violaciones = array_merge($violaciones, $v);

        $v = $this->checkHorasSemanales($detalles);
        if (empty($v)) { $checksOk++; }
        $violaciones = array_merge($violaciones, $v);

        $resultado = [
            'valido'        => empty($violaciones),
            'violaciones'   => $violaciones,
            'total_detalles'=> $detalles->count(),
            'checks_pasados'=> $checksOk,
            'checks_total'  => $total,
        ];

        Log::channel('horario')->info('INTEGRIDAD verificada', [
            'horario_id'     => $horario->id,
            'detalles'       => $detalles->count(),
            'violaciones'    => count($violaciones),
            'checks_ok'      => "{$checksOk}/{$total}",
            'integridad'     => $resultado['valido'] ? 'OK' : 'CON FALLOS',
        ]);

        return $resultado;
    }

    // =========================================================================
    //  CHECKS
    // =========================================================================

    /** I1: un docente no puede estar en dos clases al mismo tiempo. */
    private function checkDocenteDuplicado(Collection $detalles): array
    {
        $violaciones = [];
        $seen        = [];

        foreach ($detalles as $d) {
            $docId = $d->asignacion?->docente_id;
            if (! $docId) {
                continue;
            }

            $key = "{$docId}_{$d->dia}_{$d->franja_id}";

            if (isset($seen[$key])) {
                $violaciones[] = [
                    'tipo'     => 'docente_duplicado',
                    'severidad'=> 'critico',
                    'mensaje'  => sprintf(
                        '[I1] Docente "%s" aparece dos veces el %s en la franja "%s".',
                        $d->asignacion->docente?->nombre_completo ?? "ID:{$docId}",
                        ucfirst($d->dia),
                        $d->franja?->nombre ?? "franja {$d->franja_id}"
                    ),
                ];
            }

            $seen[$key] = true;
        }

        return $violaciones;
    }

    /** I2: un aula no puede albergar dos grupos al mismo tiempo. */
    private function checkAulaDuplicada(Collection $detalles): array
    {
        $violaciones = [];
        $seen        = [];

        foreach ($detalles as $d) {
            if (! $d->aula_id) {
                continue;
            }

            $key = "{$d->aula_id}_{$d->dia}_{$d->franja_id}";

            if (isset($seen[$key])) {
                $violaciones[] = [
                    'tipo'     => 'aula_duplicada',
                    'severidad'=> 'critico',
                    'mensaje'  => sprintf(
                        '[I2] Aula "%s" está ocupada dos veces el %s en la franja "%s".',
                        $d->aula?->nombre ?? "ID:{$d->aula_id}",
                        ucfirst($d->dia),
                        $d->franja?->nombre ?? "franja {$d->franja_id}"
                    ),
                ];
            }

            $seen[$key] = true;
        }

        return $violaciones;
    }

    /** I3: un grupo no puede tener dos materias al mismo tiempo. */
    private function checkGrupoDuplicado(Collection $detalles): array
    {
        $violaciones = [];
        $seen        = [];

        foreach ($detalles as $d) {
            $grupoId = $d->asignacion?->grupo_id;
            if (! $grupoId) {
                continue;
            }

            $key = "{$grupoId}_{$d->dia}_{$d->franja_id}";

            if (isset($seen[$key])) {
                $violaciones[] = [
                    'tipo'     => 'grupo_duplicado',
                    'severidad'=> 'critico',
                    'mensaje'  => sprintf(
                        '[I3] Grupo "%s" tiene dos clases el %s en la franja "%s".',
                        $d->asignacion->grupo?->nombre_completo ?? "ID:{$grupoId}",
                        ucfirst($d->dia),
                        $d->franja?->nombre ?? "franja {$d->franja_id}"
                    ),
                ];
            }

            $seen[$key] = true;
        }

        return $violaciones;
    }

    /**
     * I4: cada asignación tiene al menos sus horas_semana cubiertas.
     * Advertencia (no crítico) — puede ocurrir si el greedy no encontró slot.
     */
    private function checkHorasSemanales(Collection $detalles): array
    {
        $violaciones = [];
        $conteo      = [];

        foreach ($detalles as $d) {
            $conteo[$d->asignacion_id] = ($conteo[$d->asignacion_id] ?? 0) + 1;
        }

        $asignacionesUnicas = $detalles
            ->pluck('asignacion')
            ->filter()
            ->unique('id');

        foreach ($asignacionesUnicas as $asig) {
            $requeridas = (int) $asig->horas_semana;
            $asignadas  = $conteo[$asig->id] ?? 0;

            if ($asignadas < $requeridas) {
                $violaciones[] = [
                    'tipo'     => 'horas_insuficientes',
                    'severidad'=> 'advertencia',
                    'mensaje'  => sprintf(
                        '[I4] "%s" (grupo %s) requiere %d h/sem pero solo tiene %d asignada(s). '
                            . 'Faltan %d slot(s).',
                        $asig->asignatura?->nombre ?? '?',
                        $asig->grupo?->nombre_completo ?? '?',
                        $requeridas,
                        $asignadas,
                        $requeridas - $asignadas
                    ),
                ];
            }
        }

        return $violaciones;
    }
}
