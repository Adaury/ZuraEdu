<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\Matricula;
use App\Models\QrAsistenciaToken;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AsistenciaQrController extends Controller
{
    // ── DOCENTE ─────────────────────────────────────────────────────────────

    public function panel(Asignacion $asignacion)
    {
        $docente = Auth::user()->docente;
        abort_if($asignacion->docente_id !== $docente->id, 403);

        // Token activo vigente (si existe)
        $qrToken = QrAsistenciaToken::where('asignacion_id', $asignacion->id)
            ->where('activo', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        $schoolYear = SchoolYear::actual();

        $totalEstudiantes = Matricula::where('grupo_id', $asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->count();

        return view('portal.docente.asistencia_qr_panel',
            compact('asignacion', 'qrToken', 'totalEstudiantes'));
    }

    public function crearToken(Request $request, Asignacion $asignacion)
    {
        $docente = Auth::user()->docente;
        abort_if($asignacion->docente_id !== $docente->id, 403);

        $duracion = max(5, min(60, (int) $request->input('duracion', 15)));

        // Desactivar tokens anteriores de esta asignacion
        QrAsistenciaToken::where('asignacion_id', $asignacion->id)->update(['activo' => false]);

        QrAsistenciaToken::create([
            'token'            => Str::random(40),
            'asignacion_id'    => $asignacion->id,
            'docente_id'       => $docente->id,
            'fecha'            => now()->toDateString(),
            'expires_at'       => now()->addMinutes($duracion),
            'duracion_minutos' => $duracion,
            'activo'           => true,
        ]);

        return redirect()->route('portal.docente.asistencia.qr.panel', $asignacion);
    }

    public function estado(QrAsistenciaToken $qrToken)
    {
        $docente = Auth::user()->docente;
        abort_if($qrToken->docente_id !== $docente->id, 403);

        $registrados = Asistencia::with('matricula.estudiante')
            ->where('asignacion_id', $qrToken->asignacion_id)
            ->whereDate('fecha', $qrToken->fecha)
            ->where('estado', 'presente')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($a) => [
                'nombre' => $a->matricula?->estudiante?->nombre_completo ?? '—',
                'hora'   => $a->updated_at->format('H:i:s'),
            ]);

        return response()->json([
            'valido'     => $qrToken->isValido(),
            'restantes'  => $qrToken->segundosRestantes(),
            'registrados'=> $registrados->count(),
            'lista'      => $registrados->values(),
        ]);
    }

    public function cerrar(Request $request, QrAsistenciaToken $qrToken)
    {
        $docente = Auth::user()->docente;
        abort_if($qrToken->docente_id !== $docente->id, 403);

        $qrToken->update(['activo' => false]);

        if ($request->boolean('marcar_ausentes')) {
            $schoolYear  = SchoolYear::actual();
            $asignacion  = $qrToken->asignacion;
            $fechaStr    = $qrToken->fecha->toDateString();

            $matriculas = Matricula::where('grupo_id', $asignacion->grupo_id)
                ->where('estado', 'activa')
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->pluck('id');

            foreach ($matriculas as $mId) {
                Asistencia::firstOrCreate(
                    [
                        'matricula_id'  => $mId,
                        'asignacion_id' => $asignacion->id,
                        'fecha'         => $fechaStr,
                    ],
                    ['estado' => 'ausente', 'registrado_por' => Auth::id()]
                );
            }
        }

        return redirect()->route('portal.docente.asistencia', $qrToken->asignacion)
            ->with('success', 'Sesión QR finalizada. ' .
                ($request->boolean('marcar_ausentes') ? 'Estudiantes no registrados marcados como ausentes.' : ''));
    }

    // ── ESTUDIANTE ───────────────────────────────────────────────────────────

    public function scanView(string $token)
    {
        $qr = QrAsistenciaToken::where('token', $token)
            ->with(['asignacion.asignatura', 'asignacion.grupo.grado'])
            ->first();

        if (! $qr || ! $qr->isValido()) {
            return view('asistencia.qr_error', [
                'mensaje' => 'Este código QR ha expirado o ya no es válido.',
            ]);
        }

        $estudiante = Auth::user()->estudiante;
        if (! $estudiante) {
            return view('asistencia.qr_error', [
                'mensaje' => 'Debes iniciar sesión con tu cuenta de estudiante para registrar asistencia.',
            ]);
        }

        $schoolYear = SchoolYear::actual();

        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->where('grupo_id', $qr->asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->first();

        if (! $matricula) {
            return view('asistencia.qr_error', [
                'mensaje' => 'No estás inscrito/a en esta clase.',
            ]);
        }

        $yaRegistrado = Asistencia::where('matricula_id', $matricula->id)
            ->where('asignacion_id', $qr->asignacion_id)
            ->whereDate('fecha', $qr->fecha)
            ->where('estado', 'presente')
            ->exists();

        return view('asistencia.qr_scan', compact('qr', 'token', 'estudiante', 'matricula', 'yaRegistrado'));
    }

    public function registrar(Request $request, string $token)
    {
        $qr = QrAsistenciaToken::where('token', $token)
            ->with('asignacion')
            ->first();

        if (! $qr || ! $qr->isValido()) {
            return redirect()->route('asistencia.qr.scan', $token)
                ->with('error', 'El código QR ha expirado.');
        }

        $estudiante = Auth::user()->estudiante;
        abort_if(! $estudiante, 403);

        $schoolYear = SchoolYear::actual();

        $matricula = Matricula::where('estudiante_id', $estudiante->id)
            ->where('grupo_id', $qr->asignacion->grupo_id)
            ->where('estado', 'activa')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->first();

        abort_if(! $matricula, 403);

        Asistencia::updateOrCreate(
            [
                'matricula_id'  => $matricula->id,
                'asignacion_id' => $qr->asignacion_id,
                'fecha'         => $qr->fecha->toDateString(),
            ],
            ['estado' => 'presente', 'registrado_por' => Auth::id()]
        );

        return redirect()->route('asistencia.qr.scan', $token)
            ->with('registrado', true);
    }
}
