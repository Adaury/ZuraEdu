<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcuerdoReunion;
use App\Models\ConfigInstitucional;
use App\Models\Reunion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReunionController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Reunion::with('convocante')->latest('fecha');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($sq) use ($q) {
                $sq->where('titulo', 'like', "%{$q}%")
                   ->orWhere('lugar', 'like', "%{$q}%");
            });
        }

        $reuniones = $query->paginate(20)->withQueryString();
        $tipos     = Reunion::tiposLabel();
        $estados   = Reunion::estadosLabel();

        return view('admin.reuniones.index', compact('reuniones', 'tipos', 'estados'));
    }

    // ── Create ────────────────────────────────────────────────────────────
    public function create()
    {
        $tipos        = Reunion::tiposLabel();
        $estados      = Reunion::estadosLabel();
        $convocantes  = User::where('activo', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.reuniones.create', compact('tipos', 'estados', 'convocantes'));
    }

    // ── Store ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'        => 'required|string|max:255',
            'tipo'          => 'required|in:consejo_directivo,reunion_padres,reunion_docentes,comite,otra',
            'fecha'         => 'required|date',
            'lugar'         => 'nullable|string|max:255',
            'convocante_id' => 'nullable|exists:users,id',
            'agenda'        => 'nullable|string',
            'participantes' => 'nullable|string',
            'estado'        => 'required|in:programada,realizada,cancelada',
        ]);

        $reunion = Reunion::create($data);

        return redirect()
            ->route('admin.reuniones.show', $reunion)
            ->with('success', 'Reunión creada correctamente.');
    }

    // ── Show ──────────────────────────────────────────────────────────────
    public function show(Reunion $reunion)
    {
        $reunion->load(['convocante', 'acuerdos']);

        return view('admin.reuniones.show', compact('reunion'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    public function edit(Reunion $reunion)
    {
        $tipos       = Reunion::tiposLabel();
        $estados     = Reunion::estadosLabel();
        $convocantes = User::where('activo', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.reuniones.create', compact('reunion', 'tipos', 'estados', 'convocantes'));
    }

    // ── Update ────────────────────────────────────────────────────────────
    public function update(Request $request, Reunion $reunion)
    {
        $data = $request->validate([
            'titulo'        => 'required|string|max:255',
            'tipo'          => 'required|in:consejo_directivo,reunion_padres,reunion_docentes,comite,otra',
            'fecha'         => 'required|date',
            'lugar'         => 'nullable|string|max:255',
            'convocante_id' => 'nullable|exists:users,id',
            'agenda'        => 'nullable|string',
            'participantes' => 'nullable|string',
            'estado'        => 'required|in:programada,realizada,cancelada',
        ]);

        $reunion->update($data);

        return redirect()
            ->route('admin.reuniones.show', $reunion)
            ->with('success', 'Reunión actualizada correctamente.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────
    public function destroy(Reunion $reunion)
    {
        $reunion->delete();

        return redirect()
            ->route('admin.reuniones.index')
            ->with('success', 'Reunión eliminada.');
    }

    // ── Agregar acuerdo (POST) ────────────────────────────────────────────
    public function addAcuerdo(Request $request, Reunion $reunion)
    {
        $data = $request->validate([
            'descripcion'  => 'required|string',
            'responsable'  => 'nullable|string|max:255',
            'fecha_limite' => 'nullable|date',
        ]);

        $data['reunion_id'] = $reunion->id;
        $data['cumplido']   = false;

        AcuerdoReunion::create($data);

        return back()->with('success', 'Acuerdo registrado.');
    }

    // ── Toggle cumplido (PATCH) ───────────────────────────────────────────
    public function toggleCumplido(AcuerdoReunion $acuerdo)
    {
        $acuerdo->update(['cumplido' => !$acuerdo->cumplido]);

        return back()->with('success', $acuerdo->cumplido ? 'Marcado como cumplido.' : 'Marcado como pendiente.');
    }

    // ── PDF del Acta ──────────────────────────────────────────────────────
    public function actaPdf(Reunion $reunion)
    {
        $reunion->load(['convocante', 'acuerdos']);

        $inst = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir  = ConfigInstitucional::get('nombre_director', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.reuniones.acta_pdf',
            compact('reunion', 'inst', 'dir')
        )->setPaper('letter', 'portrait');

        $slug = Str::slug($reunion->titulo ?? 'acta');
        return $pdf->download("acta_reunion_{$slug}.pdf");
    }
}
