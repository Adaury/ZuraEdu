<?php

namespace App\Services;

use App\Models\AlertaSistema;
use App\Models\Docente;
use App\Models\HorarioDetalle;
use App\Models\Horario;
use App\Models\Notificacion;
use App\Models\Suplencia;
use App\Models\User;
use Carbon\Carbon;

class SuplenciaService
{
    /**
     * Registrar ausencia de un docente y buscar suplente automáticamente.
     *
     * @return array{suplencias: Suplencia[], sin_cubrir: int}
     *             |array{error: string}
     *             |array{advertencia: string, suplencias: array}
     */
    public function registrarAusencia(int $docenteId, Carbon $fecha, string $motivo, int $registradoPor): array
    {
        $horarioActivo = Horario::where('es_activo', true)->first();

        if (! $horarioActivo) {
            return ['error' => 'No hay horario activo.'];
        }

        $diaSemana = $this->normalizarDia($fecha);

        // Get all classes for this teacher on this day
        $clases = HorarioDetalle::with(['asignacion.grupo', 'asignacion.asignatura', 'franja'])
            ->where('horario_id', $horarioActivo->id)
            ->where('dia', $diaSemana)
            ->whereHas('asignacion', fn ($q) => $q->where('docente_id', $docenteId))
            ->get();

        if ($clases->isEmpty()) {
            return [
                'advertencia' => 'El docente no tiene clases programadas ese día.',
                'suplencias'  => [],
            ];
        }

        $suplenciasCreadas = [];

        foreach ($clases as $detalle) {
            // Skip if a suplencia already exists for this slot on this date
            $existe = Suplencia::where('horario_detalle_id', $detalle->id)
                ->where('fecha', $fecha->format('Y-m-d'))
                ->exists();

            if ($existe) {
                continue;
            }

            // Find an available substitute teacher
            $suplente = $this->buscarSuplente($docenteId, $diaSemana, $detalle->franja_id, $fecha);

            $suplencia = Suplencia::create([
                'horario_detalle_id'  => $detalle->id,
                'docente_original_id' => $docenteId,
                'docente_suplente_id' => $suplente?->id,
                'fecha'               => $fecha->format('Y-m-d'),
                'estado'              => $suplente ? 'cubierta' : 'pendiente',
                'motivo'              => $motivo,
                'registrado_por'      => $registradoPor,
            ]);

            $suplenciasCreadas[] = $suplencia;

            // Notify involved parties
            $this->notificar($suplencia, $detalle, $suplente);
        }

        return [
            'suplencias' => $suplenciasCreadas,
            'sin_cubrir' => collect($suplenciasCreadas)->where('estado', 'pendiente')->count(),
        ];
    }

    /**
     * Find a teacher who is free at the given day + franja slot.
     * Excludes the absent teacher and anyone already teaching at that time.
     */
    private function buscarSuplente(int $docenteExcluidoId, string $dia, int $franjaId, Carbon $fecha): ?Docente
    {
        // Collect all teacher IDs that are occupied at this slot in the active schedule
        $ocupados = HorarioDetalle::where('dia', $dia)
            ->where('franja_id', $franjaId)
            ->whereHas('horario', fn ($q) => $q->where('es_activo', true))
            ->with('asignacion')
            ->get()
            ->pluck('asignacion.docente_id')
            ->filter()
            ->unique()
            ->toArray();

        $ocupados[] = $docenteExcluidoId;

        return Docente::whereNotIn('id', $ocupados)
            ->where('estado', 'activo')
            ->whereHas('user', fn ($q) => $q->where('activo', true))
            ->first();
    }

    /**
     * Send in-app alerts to the substitute (if found) or to admins/directors
     * when the class cannot be covered.
     */
    private function notificar(Suplencia $suplencia, HorarioDetalle $detalle, ?Docente $suplente): void
    {
        $grupo   = $detalle->asignacion?->grupo?->nombre_completo
                ?? $detalle->asignacion?->grupo?->nombre
                ?? 'Grupo';
        $materia = $detalle->asignacion?->asignatura?->nombre ?? 'Materia';
        $hora    = $detalle->franja?->nombre_completo ?? '';
        $fecha   = $suplencia->fecha instanceof \Carbon\Carbon
            ? $suplencia->fecha->format('d/m/Y')
            : \Carbon\Carbon::parse($suplencia->fecha)->format('d/m/Y');

        $titulo  = "Suplencia: {$grupo} — {$materia} ({$fecha})";
        $mensaje = $suplente
            ? "Se requiere su suplencia para {$grupo} - {$materia} a las {$hora} el {$fecha}."
            : "Clase sin cubrir: {$grupo} - {$materia} a las {$hora} el {$fecha}. Se requiere docente disponible.";

        // Notify the substitute teacher — AlertaSistema (campanita admin) + Notificacion (portal)
        if ($suplente?->user_id) {
            AlertaSistema::create([
                'tipo'            => 'otro',
                'titulo'          => $titulo,
                'mensaje'         => $mensaje,
                'nivel'           => 'info',
                'destinatario_id' => $suplente->user_id,
                'referencia_tipo' => 'suplencia',
                'referencia_id'   => $suplencia->id,
                'leida'           => false,
                'expira_en'       => now()->addDays(3),
            ]);

            // Notificación en portal docente (campanita)
            Notificacion::create([
                'user_id'  => $suplente->user_id,
                'tipo'     => 'suplencia',
                'titulo'   => "Suplencia asignada — {$fecha}",
                'mensaje'  => $mensaje,
                'leida'    => false,
                'datos'    => json_encode(['suplencia_id' => $suplencia->id]),
            ]);
        }

        // Notify admins/directors when the class cannot be covered
        if (! $suplente) {
            $admins = User::role(['Administrador', 'Director'])->get();

            foreach ($admins as $admin) {
                AlertaSistema::create([
                    'tipo'            => 'otro',
                    'titulo'          => "⚠️ Clase sin cubrir: {$grupo}",
                    'mensaje'         => $mensaje,
                    'nivel'           => 'danger',
                    'destinatario_id' => $admin->id,
                    'referencia_tipo' => 'suplencia',
                    'referencia_id'   => $suplencia->id,
                    'leida'           => false,
                    'expira_en'       => now()->addDays(3),
                ]);
            }
        }
    }

    /**
     * Convert a Carbon date to a normalized lowercase Spanish day name
     * without accents (e.g. "miércoles" → "miercoles").
     */
    private function normalizarDia(Carbon $fecha): string
    {
        $dia = strtolower($fecha->locale('es')->dayName);

        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'u'],
            $dia
        );
    }
}
