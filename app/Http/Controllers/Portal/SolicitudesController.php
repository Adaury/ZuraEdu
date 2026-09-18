<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\SolicitudRepresentante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SolicitudesController extends Controller
{
    private function getRep(): Representante
    {
        return Representante::where('user_id', auth()->id())->firstOrFail();
    }

    public function index()
    {
        $rep = $this->getRep();

        $solicitudes = SolicitudRepresentante::where('representante_id', $rep->id)
            ->with('estudiante')
            ->orderByDesc('created_at')
            ->paginate(15);

        $pendientes = SolicitudRepresentante::where('representante_id', $rep->id)
            ->where('estado', 'pendiente')->count();

        $hijos = $rep->estudiantes()->get();

        return view('portal.padre.solicitudes.index', compact('solicitudes', 'pendientes', 'hijos', 'rep'));
    }

    public function create()
    {
        $rep   = $this->getRep();
        $hijos = $rep->estudiantes()->get();
        $tipos = SolicitudRepresentante::TIPOS;

        // GAP 08 del roadmap (cita_docente sin destinatario real): docentes
        // reales del hijo, derivados de sus asignaciones activas -- para que
        // el formulario ofrezca una lista real en vez de texto libre.
        $schoolYear = SchoolYear::actual();
        $docentesPorHijo = [];
        foreach ($hijos as $hijo) {
            $matricula = $hijo->matriculas()
                ->where('estado', 'activa')
                ->when($schoolYear, fn ($q) => $q->where('school_year_id', $schoolYear->id))
                ->latest()->first();

            $docentesPorHijo[$hijo->id] = $matricula
                ? Asignacion::with(['docente', 'asignatura'])
                    ->where('grupo_id', $matricula->grupo_id)
                    ->where('activo', true)
                    ->whereNotNull('docente_id')
                    ->get()
                    ->unique('docente_id')
                    ->map(fn ($a) => [
                        'id'    => $a->docente_id,
                        'label' => trim(($a->docente?->nombre_completo ?? 'Docente') . ' — ' . ($a->asignatura?->nombre ?? '')),
                    ])
                    ->values()
                : collect();
        }

        return view('portal.padre.solicitudes.create', compact('hijos', 'tipos', 'rep', 'docentesPorHijo'));
    }

    public function store(Request $request)
    {
        $rep = $this->getRep();

        $data = $request->validate([
            'estudiante_id' => 'nullable|integer',
            'tipo'          => 'required|in:' . implode(',', array_keys(SolicitudRepresentante::TIPOS)),
            'docente_id'    => 'nullable|integer',
            'asunto'        => 'required|string|max:200',
            'descripcion'   => 'required|string|max:3000',
            'fecha_evento'  => 'nullable|date',
            'adjunto'       => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        // Verificar que el estudiante_id pertenece al representante
        $estudianteId = null;
        if (! empty($data['estudiante_id'])) {
            $ids = $rep->estudiantes()->pluck('estudiantes.id')->toArray();
            if (in_array((int) $data['estudiante_id'], $ids)) {
                $estudianteId = (int) $data['estudiante_id'];
            }
        }

        // docente_id solo aplica a cita_docente, y solo si ese docente
        // realmente da clase al hijo elegido en su grupo activo -- nunca
        // confiar en el ID que llega del formulario (regla multi-tenant de
        // CLAUDE.md: verificar la relación real contra la BD).
        $docenteId = null;
        if ($data['tipo'] === 'cita_docente' && $estudianteId && ! empty($data['docente_id'])) {
            $schoolYear = SchoolYear::actual();
            $matricula  = Matricula::where('estudiante_id', $estudianteId)
                ->where('estado', 'activa')
                ->when($schoolYear, fn ($q) => $q->where('school_year_id', $schoolYear->id))
                ->latest()->first();

            if ($matricula && Asignacion::where('grupo_id', $matricula->grupo_id)
                    ->where('docente_id', (int) $data['docente_id'])
                    ->where('activo', true)
                    ->exists()) {
                $docenteId = (int) $data['docente_id'];
            }
        }

        $adjunto = null;
        if ($request->hasFile('adjunto')) {
            $adjunto = $request->file('adjunto')->store('solicitudes', 'public');
        }

        $solicitud = SolicitudRepresentante::create([
            'representante_id' => $rep->id,
            'estudiante_id'    => $estudianteId,
            'docente_id'       => $docenteId,
            'tipo'             => $data['tipo'],
            'asunto'           => $data['asunto'],
            'descripcion'      => $data['descripcion'],
            'fecha_evento'     => $data['fecha_evento'] ?? null,
            'adjunto'          => $adjunto,
            'estado'           => 'pendiente',
        ]);

        // Notificar a administradores/director
        User::role(['Administrador', 'Director'])->each(function ($admin) use ($rep, $data) {
            Notificacion::create([
                'user_id' => $admin->id,
                'tipo'    => 'solicitud',
                'titulo'  => 'Nueva solicitud: ' . (SolicitudRepresentante::TIPOS[$data['tipo']] ?? $data['tipo']),
                'mensaje' => "{$rep->nombre_completo} envió una solicitud: {$data['asunto']}",
                'leida'   => false,
            ]);
        });

        // Notificar también al docente destinatario de la cita, si aplica
        if ($docenteId) {
            $docente = \App\Models\Docente::find($docenteId);
            if ($docente?->user_id) {
                Notificacion::create([
                    'user_id' => $docente->user_id,
                    'tipo'    => 'solicitud',
                    'titulo'  => 'Solicitud de cita — ' . $rep->nombre_completo,
                    'mensaje' => "{$rep->nombre_completo} solicitó una cita contigo: {$data['asunto']}",
                    'leida'   => false,
                ]);
            }
        }

        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_rep_stats");
        Cache::forget("t{$tid}_sol_rep_pend");

        return redirect()->route('portal.padre.solicitudes.index')
            ->with('success', 'Solicitud enviada correctamente. El equipo del centro la revisará pronto.');
    }

    public function show(SolicitudRepresentante $solicitud)
    {
        $rep = $this->getRep();
        abort_if($solicitud->representante_id !== $rep->id, 403);

        return view('portal.padre.solicitudes.show', compact('solicitud', 'rep'));
    }
}
