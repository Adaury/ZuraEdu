<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\HasDocenteContext;
use App\Models\Notificacion;
use App\Models\SolicitudDocente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SolicitudesDocenteController extends Controller
{
    use HasDocenteContext;

    public function index()
    {
        $docente = $this->getDocente();

        $solicitudes = SolicitudDocente::where('docente_id', $docente->id)
            ->orderByRaw("FIELD(estado,'pendiente','en_proceso','aprobada','rechazada')")
            ->orderByDesc('created_at')
            ->paginate(15);

        $counts = SolicitudDocente::where('docente_id', $docente->id)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $stats = [
            'pendientes' => $counts->get('pendiente', 0),
            'en_proceso' => $counts->get('en_proceso', 0),
            'total'      => $counts->sum(),
        ];

        $tipos   = SolicitudDocente::TIPOS;
        $estados = SolicitudDocente::estados();

        return view('portal.docente.solicitudes.index',
            compact('solicitudes', 'stats', 'tipos', 'estados', 'docente'));
    }

    public function create()
    {
        $docente = $this->getDocente();
        $tipos   = SolicitudDocente::TIPOS;
        return view('portal.docente.solicitudes.create', compact('tipos', 'docente'));
    }

    public function store(Request $request)
    {
        $docente = $this->getDocente();

        $validated = $request->validate([
            'tipo'         => ['required', 'in:' . implode(',', array_keys(SolicitudDocente::TIPOS))],
            'asunto'       => ['required', 'string', 'max:200'],
            'descripcion'  => ['required', 'string', 'max:2000'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'adjunto'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:4096'],
        ]);

        $adjuntoPath = null;
        if ($request->hasFile('adjunto')) {
            $adjuntoPath = $request->file('adjunto')->store('solicitudes_docente', 'public');
        }

        $sol = SolicitudDocente::create([
            'docente_id'   => $docente->id,
            'tipo'         => $validated['tipo'],
            'asunto'       => $validated['asunto'],
            'descripcion'  => $validated['descripcion'],
            'fecha_inicio' => $validated['fecha_inicio'] ?? null,
            'fecha_fin'    => $validated['fecha_fin'] ?? null,
            'adjunto'      => $adjuntoPath,
            'estado'       => 'pendiente',
        ]);

        try {
            foreach (User::role(['Administrador', 'Director'])->get() as $admin) {
                Notificacion::enviar(
                    $admin->id,
                    'info',
                    'Nueva solicitud de docente',
                    "El/la docente {$docente->nombre_completo} envió: {$sol->asunto}.",
                    ['url' => route('admin.solicitudes-docente.show', $sol)]
                );
            }
        } catch (\Throwable) {}

        // Invalidar cache de stats admin para que muestre el nuevo pendiente
        $tid = tenant_id();
        Cache::forget("t{$tid}_solicitudes_doc_stats");
        Cache::forget("t{$tid}_sol_doc_pend");

        return redirect()->route('portal.docente.solicitudes.show', $sol)
            ->with('success', 'Solicitud enviada correctamente.');
    }

    public function show(SolicitudDocente $solicitud)
    {
        $docente = $this->getDocente();
        abort_if($solicitud->docente_id !== $docente->id, 403);

        $tipos   = SolicitudDocente::TIPOS;
        $estados = SolicitudDocente::estados();

        return view('portal.docente.solicitudes.show',
            compact('solicitud', 'tipos', 'estados', 'docente'));
    }
}
