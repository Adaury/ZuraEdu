<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class CalificacionAcademica extends Model
{
    use BelongsToTenant;

    protected $table = 'calificaciones_academicas';

    /**
     * Los 4 bloques de competencias MINERD para secundaria.
     */
    public const COMPETENCIAS = [
        1 => [
            'nombre' => 'Comunicativa',
            'color'  => '#2563eb',
            'light'  => '#dbeafe',
            'dark'   => '#1e3a8a',
            'icon'   => 'bi-chat-quote-fill',
        ],
        2 => [
            'nombre' => 'Pensamiento Lógico y Resolución de Problemas',
            'color'  => '#7c3aed',
            'light'  => '#ede9fe',
            'dark'   => '#3b0764',
            'icon'   => 'bi-lightbulb-fill',
        ],
        3 => [
            'nombre' => 'Científica, Tecnológica, Medioambiental y de la Salud',
            'color'  => '#059669',
            'light'  => '#d1fae5',
            'dark'   => '#064e3b',
            'icon'   => 'bi-gear-fill',
        ],
        4 => [
            'nombre' => 'Ética, Ciudadana y Desarrollo Espiritual',
            'color'  => '#d97706',
            'light'  => '#fef3c7',
            'dark'   => '#451a03',
            'icon'   => 'bi-heart-fill',
        ],
    ];

    protected $fillable = [
        'matricula_id', 'asignacion_id', 'school_year_id',
        // Competencia 1 — Comunicativa
        'comp1_p1', 'comp1_r1', 'avg_comp1_p1',
        'comp1_p2', 'comp1_r2', 'avg_comp1_p2',
        'comp1_p3', 'comp1_r3', 'avg_comp1_p3',
        'comp1_p4', 'comp1_r4', 'avg_comp1_p4',
        'prom_comp1',
        // Competencia 2 — Pensamiento Lógico
        'comp2_p1', 'comp2_r1', 'avg_comp2_p1',
        'comp2_p2', 'comp2_r2', 'avg_comp2_p2',
        'comp2_p3', 'comp2_r3', 'avg_comp2_p3',
        'comp2_p4', 'comp2_r4', 'avg_comp2_p4',
        'prom_comp2',
        // Competencia 3 — Científica/Tecnológica
        'comp3_p1', 'comp3_r1', 'avg_comp3_p1',
        'comp3_p2', 'comp3_r2', 'avg_comp3_p2',
        'comp3_p3', 'comp3_r3', 'avg_comp3_p3',
        'comp3_p4', 'comp3_r4', 'avg_comp3_p4',
        'prom_comp3',
        // Competencia 4 — Ética/Ciudadana
        'comp4_p1', 'comp4_r1', 'avg_comp4_p1',
        'comp4_p2', 'comp4_r2', 'avg_comp4_p2',
        'comp4_p3', 'comp4_r3', 'avg_comp4_p3',
        'comp4_p4', 'comp4_r4', 'avg_comp4_p4',
        'prom_comp4',
        // Finales
        'nota_final', 'nota_cc', 'nota_completiva', 'nota_ce', 'nota_extraordinaria',
        'eval_cf', 'eval_ce', 'situacion', 'recuperaciones_acad',
        // Asistencia
        'asist_p1', 'asist_p2', 'asist_p3', 'asist_p4',
        'clases_p1', 'clases_p2', 'clases_p3', 'clases_p4',
        'pct_asistencia',
        // Meta
        'indicador', 'observaciones', 'publicado', 'modificado_por',
    ];

    protected $casts = [
        'publicado'           => 'boolean',
        'nota_final'          => 'float',
        'nota_cc'             => 'float',
        'nota_completiva'     => 'float',
        'nota_ce'             => 'float',
        'nota_extraordinaria' => 'float',
        'eval_cf'             => 'float',
        'eval_ce'             => 'float',
        'pct_asistencia'      => 'float',
        'recuperaciones_acad' => 'array',
        'prom_comp1' => 'float', 'prom_comp2' => 'float',
        'prom_comp3' => 'float', 'prom_comp4' => 'float',
        // Proceso
        'comp1_p1' => 'float', 'comp1_p2' => 'float', 'comp1_p3' => 'float', 'comp1_p4' => 'float',
        'comp2_p1' => 'float', 'comp2_p2' => 'float', 'comp2_p3' => 'float', 'comp2_p4' => 'float',
        'comp3_p1' => 'float', 'comp3_p2' => 'float', 'comp3_p3' => 'float', 'comp3_p4' => 'float',
        'comp4_p1' => 'float', 'comp4_p2' => 'float', 'comp4_p3' => 'float', 'comp4_p4' => 'float',
        // Resultado (nuevos)
        'comp1_r1' => 'float', 'comp1_r2' => 'float', 'comp1_r3' => 'float', 'comp1_r4' => 'float',
        'comp2_r1' => 'float', 'comp2_r2' => 'float', 'comp2_r3' => 'float', 'comp2_r4' => 'float',
        'comp3_r1' => 'float', 'comp3_r2' => 'float', 'comp3_r3' => 'float', 'comp3_r4' => 'float',
        'comp4_r1' => 'float', 'comp4_r2' => 'float', 'comp4_r3' => 'float', 'comp4_r4' => 'float',
        // Promedios de período por competencia (caché)
        'avg_comp1_p1' => 'float', 'avg_comp1_p2' => 'float', 'avg_comp1_p3' => 'float', 'avg_comp1_p4' => 'float',
        'avg_comp2_p1' => 'float', 'avg_comp2_p2' => 'float', 'avg_comp2_p3' => 'float', 'avg_comp2_p4' => 'float',
        'avg_comp3_p1' => 'float', 'avg_comp3_p2' => 'float', 'avg_comp3_p3' => 'float', 'avg_comp3_p4' => 'float',
        'avg_comp4_p1' => 'float', 'avg_comp4_p2' => 'float', 'avg_comp4_p3' => 'float', 'avg_comp4_p4' => 'float',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function modificadoPor()
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }

    public function scopePublicadas($q)
    {
        return $q->where('publicado', true);
    }

    // ── Helpers de cálculo ────────────────────────────────────────────────

    /**
     * Calcula la nota FINAL de un período para una competencia.
     *
     * FINAL = P + R  (donde R es recuperación ≤ 100 − P)
     * Si solo hay P → P  |  ninguno → null
     *
     * NOTA: R NO es nota independiente. Es recuperación sobre el faltante.
     */
    public function calcAvgPeriodo(int $comp, int $periodo): ?float
    {
        $p = $this->{"comp{$comp}_p{$periodo}"};  // Nota base
        $r = $this->{"comp{$comp}_r{$periodo}"};  // Recuperación (opcional)

        if ($p === null) return null;

        // Recuperación solo aplica cuando P < 70 (estudiante no alcanzó el umbral)
        if ($r !== null && (float) $p < 70.0) {
            $maxR = max(0.0, 100.0 - (float) $p);
            return round((float) $p + min((float) $r, $maxR), 2);
        }

        return round((float) $p, 2);
    }

    /**
     * Calcula el promedio de una competencia (avg de sus 4 períodos no-nulos).
     */
    public function calcPromComp(int $comp): ?float
    {
        $vals = [];
        for ($p = 1; $p <= 4; $p++) {
            $v = $this->calcAvgPeriodo($comp, $p);
            if ($v !== null) $vals[] = $v;
        }
        if (empty($vals)) return null;
        return round(array_sum($vals) / count($vals), 2);
    }

    /**
     * Recalcula y persiste todos los promedios (avg_compC_pP, prom_compC, nota_final).
     * Llama a este método después de actualizar cualquier celda P o R.
     */
    public function recalcularPromedios(): void
    {
        $updates = [];

        // Promedios de período por competencia
        for ($c = 1; $c <= 4; $c++) {
            for ($p = 1; $p <= 4; $p++) {
                $updates["avg_comp{$c}_p{$p}"] = $this->calcAvgPeriodo($c, $p);
            }
            $updates["prom_comp{$c}"] = $this->calcPromComp($c);
        }

        // Nota final = avg de los prom_compC no-nulos
        $promeds = array_filter(array_map(
            fn($c) => $updates["prom_comp{$c}"],
            [1, 2, 3, 4]
        ), fn($v) => $v !== null);

        $updates['nota_final'] = !empty($promeds)
            ? round(array_sum($promeds) / count($promeds), 2)
            : null;

        // Completivo: 50% NF + 50% CC (nota_cc ya guardado en el modelo)
        $updates['nota_completiva'] = ($updates['nota_final'] !== null && $this->nota_cc !== null)
            ? round(0.5 * $updates['nota_final'] + 0.5 * (float) $this->nota_cc, 2)
            : null;

        // Extraordinario: 30% NF + 70% CE
        $updates['nota_extraordinaria'] = ($updates['nota_final'] !== null && $this->nota_ce !== null)
            ? round(0.3 * $updates['nota_final'] + 0.7 * (float) $this->nota_ce, 2)
            : null;

        // Situación según la mejor nota final disponible
        $gradeFinal = $updates['nota_extraordinaria'] ?? $updates['nota_completiva'] ?? $updates['nota_final'];
        $updates['situacion'] = $gradeFinal !== null
            ? ($gradeFinal >= 70 ? 'A' : 'R')
            : null;

        $this->update($updates);
    }

    /**
     * Devuelve los datos de esta calificación como array para respuestas AJAX.
     * Incluye `bases[c][p]` = nota P (para que el JS actualice el faltante tras guardar).
     */
    public function toAjaxArray(): array
    {
        return [
            'prom_comp1'          => $this->prom_comp1,
            'prom_comp2'          => $this->prom_comp2,
            'prom_comp3'          => $this->prom_comp3,
            'prom_comp4'          => $this->prom_comp4,
            'nota_final'          => $this->nota_final,
            'nota_cc'             => $this->nota_cc,
            'nota_completiva'     => $this->nota_completiva,
            'nota_ce'             => $this->nota_ce,
            'nota_extraordinaria' => $this->nota_extraordinaria,
            'situacion'           => $this->situacion,
            'avgs'                => $this->getPeriodAveragesArray(),
            'bases'               => $this->getPeriodBasesArray(),
        ];
    }

    /**
     * Retorna [comp][periodo] → nota FINAL (P + R) para el frontend.
     */
    public function getPeriodAveragesArray(): array
    {
        $result = [];
        for ($c = 1; $c <= 4; $c++) {
            for ($p = 1; $p <= 4; $p++) {
                $result[$c][$p] = $this->{"avg_comp{$c}_p{$p}"};
            }
        }
        return $result;
    }

    /**
     * Retorna [comp][periodo] → nota base P (sin recuperación).
     * Permite al frontend calcular el faltante tras guardar.
     */
    public function getPeriodBasesArray(): array
    {
        $result = [];
        for ($c = 1; $c <= 4; $c++) {
            for ($p = 1; $p <= 4; $p++) {
                $result[$c][$p] = $this->{"comp{$c}_p{$p}"};
            }
        }
        return $result;
    }
}
