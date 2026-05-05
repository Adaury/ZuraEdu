<?php

namespace App\Console\Commands;

use App\Models\AlertaSistema;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Console\Command;

class AlertasRendimiento extends Command
{
    protected $signature   = 'alertas:rendimiento {--force : Regenerar aunque la alerta ya exista}';
    protected $description = 'Genera alertas de riesgo académico para estudiantes con calificaciones bajas (< 60)';

    public function handle(): int
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            $this->warn('No hay año escolar activo.');
            return self::SUCCESS;
        }

        $this->info("Procesando año escolar: {$schoolYear->nombre}");

        $creadas  = 0;
        $omitidas = 0;

        // ── Calificaciones técnicas publicadas con nota_final < 60 ──────────
        $tecnicas = Calificacion::with([
                'matricula.estudiante', 'matricula.grupo',
                'asignacion.asignatura', 'asignacion.docente',
            ])
            ->whereHas('asignacion', fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->where('publicado', true)
            ->whereNotNull('nota_final')
            ->where('nota_final', '<', 60)
            ->get();

        foreach ($tecnicas as $cal) {
            $docenteUserId = $cal->asignacion?->docente?->user_id;
            $created = $this->crearAlerta(
                $schoolYear->id,
                $cal->matricula,
                $cal->asignacion?->asignatura?->nombre ?? 'Materia',
                (float) $cal->nota_final,
                "tecnica_{$cal->id}",
                $docenteUserId
            );
            $created ? $creadas++ : $omitidas++;
        }

        // ── Calificaciones académicas publicadas con nota_final < 60 ────────
        $academicas = CalificacionAcademica::with([
                'matricula.estudiante', 'matricula.grupo',
                'asignacion.asignatura', 'asignacion.docente',
            ])
            ->where('school_year_id', $schoolYear->id)
            ->where('publicado', true)
            ->whereNotNull('nota_final')
            ->where('nota_final', '<', 60)
            ->get();

        foreach ($academicas as $cal) {
            $docenteUserId = $cal->asignacion?->docente?->user_id;
            $created = $this->crearAlerta(
                $schoolYear->id,
                $cal->matricula,
                $cal->asignacion?->asignatura?->nombre ?? 'Materia',
                (float) $cal->nota_final,
                "academica_{$cal->id}",
                $docenteUserId
            );
            $created ? $creadas++ : $omitidas++;
        }

        $this->info("Alertas creadas: {$creadas} | Ya existían: {$omitidas}");
        return self::SUCCESS;
    }

    private function crearAlerta(
        int $schoolYearId,
        ?Matricula $matricula,
        string $asignatura,
        float $nota,
        string $refId,
        ?int $docenteUserId = null
    ): bool {
        if (! $matricula?->estudiante) {
            return false;
        }

        $estudiante = $matricula->estudiante;
        $grupo      = $matricula->grupo;
        $refTipo    = 'riesgo_academico_' . $refId;

        // Coordinadores y administradores
        $destinatarios = User::role([
            'Administrador',
            'Director',
            'Coordinador Académico',
            'Coordinador Primer Ciclo',
            'Coordinador Segundo Ciclo',
            'Encargado de Área',
        ])->get();

        // También notificar al docente que imparte la materia
        if ($docenteUserId) {
            $docenteUser = User::find($docenteUserId);
            if ($docenteUser && ! $destinatarios->contains('id', $docenteUser->id)) {
                $destinatarios->push($docenteUser);
            }
        }

        $alguienCreado = false;

        foreach ($destinatarios as $dest) {
            if (! $this->option('force')) {
                $existe = AlertaSistema::where('tipo', 'riesgo_academico')
                    ->where('referencia_tipo', $refTipo)
                    ->where('destinatario_id', $dest->id)
                    ->where('leida', false)
                    ->exists();

                if ($existe) {
                    continue;
                }
            }

            AlertaSistema::create([
                'tipo'             => 'riesgo_academico',
                'titulo'           => "Riesgo académico: {$estudiante->nombre_completo}",
                'mensaje'          => "El estudiante {$estudiante->nombre_completo}" .
                                      ($grupo ? " ({$grupo->nombre_completo})" : '') .
                                      " tiene nota {$nota} en {$asignatura}. Requiere atención.",
                'nivel'            => $nota < 40 ? 'danger' : 'warning',
                'destinatario_id'  => $dest->id,
                'destinatario_rol' => null,
                'referencia_tipo'  => $refTipo,
                'referencia_id'    => $matricula->id,
                'leida'            => false,
                'school_year_id'   => $schoolYearId,
                'expira_en'        => now()->addDays(30),
            ]);

            $alguienCreado = true;
        }

        return $alguienCreado;
    }
}
