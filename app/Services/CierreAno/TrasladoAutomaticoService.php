<?php

namespace App\Services\CierreAno;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Traslado automático de fin de año: pasa a los estudiantes del año cerrado al año nuevo.
 *   · promovidos     → grado siguiente (nivel + 1), misma sección si hay cupo
 *   · no promovidos  → el MISMO grado y sección (repiten)
 *   · último grado promovido → egresa (no hay grado siguiente: no se matricula)
 *   · sin decisión (matrícula 'activa', p. ej. sin notas) → no se mueve; se cuenta para que el director lo decida
 *
 * Todo se calcula aquí, en el servidor, a partir de las matrículas reales del año base: no se acepta ninguna lista de
 * estudiantes ni de grupos del navegador. Respeta `grupos.capacidad` (si la sección está llena usa otro grupo del mismo
 * grado con cupo), continúa la numeración de lista de cada grupo y se puede ejecutar varias veces sin duplicar
 * (los ya matriculados en el año nuevo se saltan).
 */
class TrasladoAutomaticoService
{
    /**
     * Calcula qué haría el traslado SIN escribir nada.
     *
     * @return array{traslados: array<int,array{estudiante_id:int,matricula_id:int,grupo_id:int,tipo:string,excede_cupo:bool,orden:int}>,
     *               avanzan:int, repiten:int, egresan:int, pendientes:int, ya_matriculados:int, sin_grupo:array<int,string>, exceden_cupo:int}
     */
    public function planificar(SchoolYear $base, SchoolYear $nuevo): array
    {
        $grados      = Grado::where('activo', true)->orderBy('orden')->get();
        $siguiente   = [];
        foreach ($grados as $g) {
            $siguiente[$g->id] = $grados->firstWhere('nivel', $g->nivel + 1)?->id;
        }

        // Estado actual del año nuevo: cuántos hay y cuál es el último número de lista de cada grupo.
        $grupos = Grupo::where('school_year_id', $nuevo->id)->where('activo', true)->get();
        $ocupados = Matricula::where('school_year_id', $nuevo->id)
            ->selectRaw('grupo_id, count(*) n, coalesce(max(numero_orden), 0) maximo')
            ->groupBy('grupo_id')->get()->keyBy('grupo_id');
        $cuenta = $orden = [];
        foreach ($grupos as $g) {
            $cuenta[$g->id] = (int) ($ocupados[$g->id]->n ?? 0);
            $orden[$g->id]  = (int) ($ocupados[$g->id]->maximo ?? 0);
        }

        $yaMatriculados = Matricula::where('school_year_id', $nuevo->id)->pluck('estudiante_id')->flip();

        $origen = Matricula::where('school_year_id', $base->id)
            ->whereIn('estado', ['promovida', 'no_promovida'])
            ->with('grupo')
            ->get()
            ->sortBy([fn ($a, $b) => [$a->grupo->grado_id, $a->grupo->seccion_id, $a->numero_orden] <=> [$b->grupo->grado_id, $b->grupo->seccion_id, $b->numero_orden]]);

        $r = ['traslados' => [], 'avanzan' => 0, 'repiten' => 0, 'egresan' => 0, 'pendientes' => 0,
              'ya_matriculados' => 0, 'sin_grupo' => [], 'exceden_cupo' => 0];

        $r['pendientes'] = Matricula::where('school_year_id', $base->id)->where('estado', 'activa')->count();

        foreach ($origen as $m) {
            if ($yaMatriculados->has($m->estudiante_id)) {
                $r['ya_matriculados']++;
                continue;
            }

            $gradoOrigen = $m->grupo->grado_id;
            $repite      = $m->estado === 'no_promovida';
            $gradoDestino = $repite ? $gradoOrigen : ($siguiente[$gradoOrigen] ?? null);

            if (! $gradoDestino) {
                $r['egresan']++;
                continue;
            }

            $candidatos = $grupos->where('grado_id', $gradoDestino);
            if ($candidatos->isEmpty()) {
                $r['sin_grupo'][] = "matrícula {$m->id} (no hay grupo del grado destino en {$nuevo->nombre})";
                continue;
            }

            $conCupo  = fn ($g) => $cuenta[$g->id] < (int) $g->capacidad;
            $mismaSec = $candidatos->firstWhere('seccion_id', $m->grupo->seccion_id);

            if ($mismaSec && $conCupo($mismaSec)) {
                $destino = $mismaSec;                       // lo normal: misma sección
            } elseif ($otro = $candidatos->filter($conCupo)->sortBy(fn ($g) => $cuenta[$g->id])->first()) {
                $destino = $otro;                           // sección llena: el grupo del mismo grado con más cupo
            } else {
                $destino = $mismaSec ?? $candidatos->first(); // todo lleno: se matricula igual y se avisa
            }

            $excede = ! $conCupo($destino);
            $cuenta[$destino->id]++;
            $orden[$destino->id]++;

            $r['traslados'][] = [
                'estudiante_id' => $m->estudiante_id, 'matricula_id' => $m->id, 'grupo_id' => $destino->id,
                'tipo' => $repite ? 'repite' : 'avanza', 'excede_cupo' => $excede, 'orden' => $orden[$destino->id],
            ];
            $repite ? $r['repiten']++ : $r['avanzan']++;
            $excede && $r['exceden_cupo']++;
        }

        return $r;
    }

    /**
     * Ejecuta el traslado. Bloquea los grupos del año nuevo y vuelve a calcular DENTRO de la transacción, así dos traslados
     * simultáneos no repiten número de lista ni sobrepasan el cupo.
     */
    public function ejecutar(SchoolYear $base, SchoolYear $nuevo): array
    {
        $plan = DB::transaction(function () use ($base, $nuevo) {
            Grupo::where('school_year_id', $nuevo->id)->orderBy('id')->lockForUpdate()->get();

            $plan = $this->planificar($base, $nuevo);
            $hoy  = now()->toDateString();

            foreach ($plan['traslados'] as $t) {
                Matricula::create([
                    'school_year_id'  => $nuevo->id,
                    'estudiante_id'   => $t['estudiante_id'],
                    'grupo_id'        => $t['grupo_id'],
                    'fecha_matricula' => $hoy,
                    'estado'          => 'activa',
                    'numero_orden'    => $t['orden'],
                ]);
            }

            return $plan;
        });

        Log::info('Traslado automático de fin de año', [
            'ano_base' => $base->id, 'ano_nuevo' => $nuevo->id, 'avanzan' => $plan['avanzan'], 'repiten' => $plan['repiten'],
            'egresan' => $plan['egresan'], 'pendientes' => $plan['pendientes'], 'exceden_cupo' => $plan['exceden_cupo'],
            'usuario' => auth()->id(),
        ]);

        return $plan;
    }
}
