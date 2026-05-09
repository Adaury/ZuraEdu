<?php

namespace App\Console\Commands;

use App\Helpers\Setting;
use App\Mail\AlertaInasistencia;
use App\Models\AlertaSistema;
use App\Models\Asistencia;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AlertasAusencias extends Command
{
    protected $signature   = 'alertas:ausencias {--force : Regenerar aunque la alerta ya exista}';
    protected $description = 'Detecta estudiantes con ausencias repetidas y genera alertas';

    public function handle(): int
    {
        $sy = SchoolYear::actual();
        if (! $sy) {
            $this->warn('No hay año escolar activo.');
            return self::SUCCESS;
        }

        $diasVentana       = (int) Setting::get('alerta_ausencias_dias_ventana', '14');
        $minAusencias      = (int) Setting::get('alerta_ausencias_consecutivas', '3');
        $emailRepresentante = Setting::get('email_notif_ausencias', '1') === '1';

        $desde = now()->subDays($diasVentana)->toDateString();

        // Agrupar ausencias por matrícula y asignación en la ventana de tiempo
        $ausenciasAgrupadas = Asistencia::where('estado', 'ausente')
            ->where('fecha', '>=', $desde)
            ->selectRaw('matricula_id, asignacion_id, COUNT(*) as total')
            ->groupBy('matricula_id', 'asignacion_id')
            ->having('total', '>=', $minAusencias)
            ->get();

        if ($ausenciasAgrupadas->isEmpty()) {
            $this->info("Sin estudiantes con ≥{$minAusencias} ausencias en los últimos {$diasVentana} días.");
            return self::SUCCESS;
        }

        $matriculaIds = $ausenciasAgrupadas->pluck('matricula_id')->unique();
        $matriculas   = Matricula::with([
            'estudiante.representantes',
            'grupo.grado',
            'grupo.seccion',
        ])->whereIn('id', $matriculaIds)->where('school_year_id', $sy->id)->get()->keyBy('id');

        $admins = User::role(['Administrador', 'Director', 'Coordinador Académico',
            'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo'])->get();

        $inst     = \App\Models\ConfigInstitucional::withoutGlobalScopes()
            ->where('clave', 'nombre_institucion')->value('valor') ?? config('app.name');
        $creadas  = 0;
        $omitidas = 0;
        $emailsEnviados = 0;

        foreach ($ausenciasAgrupadas as $row) {
            $matricula = $matriculas->get($row->matricula_id);
            if (! $matricula?->estudiante) continue;

            $estudiante = $matricula->estudiante;
            $totalAus   = (int) $row->total;

            // Calcular % asistencia global del estudiante en esa asignación
            $totalClases = Asistencia::where('matricula_id', $matricula->id)
                ->where('asignacion_id', $row->asignacion_id)
                ->count();
            $pctAsistencia = $totalClases > 0
                ? round((($totalClases - $totalAus) / $totalClases) * 100, 1)
                : 0;

            $ref = "ausencias_mat{$matricula->id}_asig{$row->asignacion_id}";

            // ── Alertas internas para coordinación ──────────────────────────
            foreach ($admins as $dest) {
                if (! $this->option('force')) {
                    $existe = AlertaSistema::where('tipo', 'baja_asistencia')
                        ->where('referencia_tipo', 'ausencias_recientes')
                        ->where('referencia_id', $matricula->id)
                        ->where('destinatario_id', $dest->id)
                        ->where('leida', false)
                        ->where('created_at', '>=', now()->subDays($diasVentana))
                        ->exists();
                    if ($existe) { $omitidas++; continue; }
                }

                AlertaSistema::create([
                    'tipo'            => 'baja_asistencia',
                    'titulo'          => "Ausencias: {$estudiante->apellidos}, {$estudiante->nombres}",
                    'mensaje'         => "{$estudiante->nombre_completo} registró {$totalAus} ausencias "
                        . "en los últimos {$diasVentana} días ({$pctAsistencia}% asistencia). "
                        . "Grupo: {$matricula->grupo?->nombre_completo}.",
                    'nivel'           => $pctAsistencia < 60 ? 'danger' : 'warning',
                    'destinatario_id' => $dest->id,
                    'referencia_tipo' => 'ausencias_recientes',
                    'referencia_id'   => $matricula->id,
                    'school_year_id'  => $sy->id,
                    'leida'           => false,
                    'expira_en'       => now()->addDays(7),
                ]);
                $creadas++;
            }

            // ── Email al representante ───────────────────────────────────────
            if ($emailRepresentante) {
                $rep = $estudiante->representantes->first();
                if ($rep?->email) {
                    try {
                        // Pasamos asignacion_id resuelto como un objeto mínimo para el Mailable
                        $asignacion = \App\Models\Asignacion::with('asignatura')
                            ->find($row->asignacion_id);
                        if ($asignacion) {
                            Mail::to($rep->email)
                                ->send(new AlertaInasistencia($estudiante, $asignacion, $totalAus, $pctAsistencia));
                            $emailsEnviados++;
                        }
                    } catch (\Throwable $e) {
                        $this->warn("Email no enviado a {$rep->email}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("Alertas creadas: {$creadas} | Omitidas: {$omitidas} | Emails enviados: {$emailsEnviados}");
        return self::SUCCESS;
    }
}
