<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Representante;
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

        return view('portal.padre.solicitudes.create', compact('hijos', 'tipos', 'rep'));
    }

    public function store(Request $request)
    {
        $rep = $this->getRep();

        $data = $request->validate([
            'estudiante_id' => 'nullable|integer',
            'tipo'          => 'required|in:' . implode(',', array_keys(SolicitudRepresentante::TIPOS)),
            'asunto'        => 'required|string|max:200',
            'descripcion'   => 'required|string|max:3000',
            'fecha_evento'  => 'nullable|date',
            'adjunto'       => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        // Verificar que el estudiante_id pertenece al representante
        if (! empty($data['estudiante_id'])) {
            $ids = $rep->estudiantes()->pluck('estudiantes.id')->toArray();
            if (! in_array((int) $data['estudiante_id'], $ids)) {
                $data['estudiante_id'] = null;
            }
        }

        $adjunto = null;
        if ($request->hasFile('adjunto')) {
            $adjunto = $request->file('adjunto')->store('solicitudes', 'public');
        }

        SolicitudRepresentante::create([
            'representante_id' => $rep->id,
            'estudiante_id'    => $data['estudiante_id'] ?? null,
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
