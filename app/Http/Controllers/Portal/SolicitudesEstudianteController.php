<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\SolicitudEstudiante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SolicitudesEstudianteController extends Controller
{
    private function getEstudiante()
    {
        $est = Auth::user()->estudiante;
        abort_if(! $est, 403, 'Sin perfil de estudiante.');
        return $est;
    }

    public function index()
    {
        $estudiante = $this->getEstudiante();

        $solicitudes = SolicitudEstudiante::where('estudiante_id', $estudiante->id)
            ->orderByRaw("FIELD(estado,'pendiente','en_proceso','aprobada','rechazada')")
            ->orderByDesc('created_at')
            ->paginate(15);

        $counts = SolicitudEstudiante::where('estudiante_id', $estudiante->id)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $stats = [
            'pendientes' => $counts->get('pendiente', 0),
            'en_proceso' => $counts->get('en_proceso', 0),
            'total'      => $counts->sum(),
        ];

        $tipos   = SolicitudEstudiante::TIPOS;
        $estados = SolicitudEstudiante::estados();

        return view('portal.estudiante.solicitudes.index',
            compact('solicitudes', 'stats', 'tipos', 'estados', 'estudiante'));
    }

    public function create()
    {
        $estudiante = $this->getEstudiante();
        $tipos = SolicitudEstudiante::TIPOS;
        return view('portal.estudiante.solicitudes.create', compact('tipos', 'estudiante'));
    }

    public function store(Request $request)
    {
        $estudiante = $this->getEstudiante();

        $validated = $request->validate([
            'tipo'         => ['required', 'in:' . implode(',', array_keys(SolicitudEstudiante::TIPOS))],
            'asunto'       => ['required', 'string', 'max:200'],
            'descripcion'  => ['required', 'string', 'max:2000'],
            'fecha_evento' => ['nullable', 'date'],
            'adjunto'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:4096'],
        ]);

        $adjuntoPath = null;
        if ($request->hasFile('adjunto')) {
            $adjuntoPath = $request->file('adjunto')->store('solicitudes_estudiante', 'public');
        }

        $sol = SolicitudEstudiante::create([
            'estudiante_id' => $estudiante->id,
            'tipo'          => $validated['tipo'],
            'asunto'        => $validated['asunto'],
            'descripcion'   => $validated['descripcion'],
            'fecha_evento'  => $validated['fecha_evento'] ?? null,
            'adjunto'       => $adjuntoPath,
            'estado'        => 'pendiente',
        ]);

        try {
            foreach (User::role(['Administrador', 'Director'])->get() as $admin) {
                Notificacion::enviar(
                    $admin->id,
                    'info',
                    'Nueva solicitud de estudiante',
                    "El/la estudiante {$estudiante->nombre_completo} envió una solicitud: {$sol->asunto}.",
                    ['url' => route('admin.solicitudes-est.show', $sol)],
                );
            }
        } catch (\Throwable) {}

        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_est_stats");
        Cache::forget("t{$tid}_sol_est_pend");
        Cache::forget("t{$tid}_user_" . auth()->id() . '_sol_est_pend');

        return redirect()->route('portal.estudiante.solicitudes.show', $sol)
            ->with('success', 'Solicitud enviada correctamente.');
    }

    public function show(SolicitudEstudiante $solicitud)
    {
        $estudiante = $this->getEstudiante();
        abort_if($solicitud->estudiante_id !== $estudiante->id, 403);

        $tipos   = SolicitudEstudiante::TIPOS;
        $estados = SolicitudEstudiante::estados();

        return view('portal.estudiante.solicitudes.show',
            compact('solicitud', 'tipos', 'estados', 'estudiante'));
    }
}
