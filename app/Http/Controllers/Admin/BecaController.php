<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\Setting;
use App\Models\Beca;
use App\Models\BecaEstudiante;
use App\Models\ConfigInstitucional;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class BecaController extends Controller
{
    /* ════════════════════════════════════════════════════════════════════
     *  CRUD DE BECAS
     * ════════════════════════════════════════════════════════════════════ */

    // ── Listado de becas ──────────────────────────────────────────────────
    public function index(Request $request)
    {
        $q = Beca::withCount(['asignacionesActivas as becados_count']);

        if ($request->filled('buscar')) {
            $term = $request->buscar;
            $q->where(fn($s) =>
                $s->where('nombre', 'like', "%{$term}%")
                  ->orWhere('criterio', 'like', "%{$term}%")
            );
        }

        if ($request->filled('tipo')) {
            $q->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $q->where('activo', $request->estado === 'activo');
        }

        $becas = $q->orderBy('nombre')->paginate(20)->withQueryString();

        $stats = [
            'total'    => Beca::count(),
            'activas'  => Beca::activas()->count(),
            'becados'  => BecaEstudiante::activas()->count(),
        ];

        return view('admin.becas.index', compact('becas', 'stats'));
    }

    // ── Formulario crear ──────────────────────────────────────────────────
    public function create()
    {
        return view('admin.becas.create', ['beca' => new Beca()]);
    }

    // ── Guardar nueva beca ────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:150|unique:becas,nombre',
            'descripcion' => 'nullable|string|max:500',
            'tipo'        => 'required|in:porcentaje,monto_fijo',
            'valor'       => 'required|numeric|min:0.01',
            'criterio'    => 'nullable|string|max:255',
            'activo'      => 'nullable|boolean',
        ]);

        if ($data['tipo'] === 'porcentaje' && $data['valor'] > 100) {
            return back()->withInput()
                ->withErrors(['valor' => 'El porcentaje no puede superar 100%.']);
        }

        $data['activo'] = $request->boolean('activo', true);

        Beca::create($data);

        return redirect()->route('admin.becas.index')
            ->with('success', 'Beca creada correctamente.');
    }

    // ── Formulario editar ─────────────────────────────────────────────────
    public function edit(Beca $beca)
    {
        return view('admin.becas.create', compact('beca'));
    }

    // ── Actualizar ────────────────────────────────────────────────────────
    public function update(Request $request, Beca $beca)
    {
        $data = $request->validate([
            'nombre'      => "required|string|max:150|unique:becas,nombre,{$beca->id}",
            'descripcion' => 'nullable|string|max:500',
            'tipo'        => 'required|in:porcentaje,monto_fijo',
            'valor'       => 'required|numeric|min:0.01',
            'criterio'    => 'nullable|string|max:255',
            'activo'      => 'nullable|boolean',
        ]);

        if ($data['tipo'] === 'porcentaje' && $data['valor'] > 100) {
            return back()->withInput()
                ->withErrors(['valor' => 'El porcentaje no puede superar 100%.']);
        }

        $data['activo'] = $request->boolean('activo', true);

        $beca->update($data);

        return redirect()->route('admin.becas.index')
            ->with('success', 'Beca actualizada correctamente.');
    }

    // ── Eliminar ──────────────────────────────────────────────────────────
    public function destroy(Beca $beca)
    {
        if ($beca->becasEstudiante()->exists()) {
            return back()->with('error', 'No se puede eliminar: la beca tiene estudiantes asignados.');
        }

        $beca->delete();

        return back()->with('success', 'Beca eliminada.');
    }

    /* ════════════════════════════════════════════════════════════════════
     *  ASIGNACIÓN DE BECAS A MATRÍCULAS
     * ════════════════════════════════════════════════════════════════════ */

    // ── Lista de becados ──────────────────────────────────────────────────
    public function listaBecados(Request $request)
    {
        $syActual = SchoolYear::actual();

        $q = BecaEstudiante::with([
                'beca',
                'matricula.estudiante',
                'matricula.grupo.grado',
                'matricula.grupo.seccion',
            ])
            ->whereHas('matricula', fn($m) => $m->where('school_year_id', $syActual?->id));

        if ($request->filled('beca_id')) {
            $q->where('beca_id', $request->beca_id);
        }

        if ($request->filled('activo')) {
            $q->where('activo', $request->activo === '1');
        }

        if ($request->filled('buscar')) {
            $term = $request->buscar;
            $q->whereHas('matricula.estudiante', fn($e) =>
                $e->where('nombre', 'like', "%{$term}%")
                  ->orWhere('apellido', 'like', "%{$term}%")
                  ->orWhere('nombres', 'like', "%{$term}%")
                  ->orWhere('apellidos', 'like', "%{$term}%")
            );
        }

        $becados = $q->orderByDesc('activo')
                     ->orderBy('created_at')
                     ->paginate(30)
                     ->withQueryString();

        $becas      = Beca::orderBy('nombre')->get();
        $mon        = Setting::get('payments_currency', 'DOP');

        return view('admin.becas.becados', compact('becados', 'becas', 'syActual', 'mon'));
    }

    // ── Asignar beca a matrícula ──────────────────────────────────────────
    public function asignarBeca(Request $request)
    {
        $data = $request->validate([
            'beca_id'      => 'required|exists:becas,id',
            'matricula_id' => 'required|exists:matriculas,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'notas'        => 'nullable|string|max:500',
        ]);

        // Si ya existe el registro, reactivarlo
        $existente = BecaEstudiante::where('beca_id', $data['beca_id'])
            ->where('matricula_id', $data['matricula_id'])
            ->first();

        if ($existente) {
            $existente->update([
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin'    => $data['fecha_fin'] ?? null,
                'activo'       => true,
                'notas'        => $data['notas'] ?? null,
            ]);
        } else {
            $data['activo'] = true;
            BecaEstudiante::create($data);
        }

        return redirect()->route('admin.becas.becados')
            ->with('success', 'Beca asignada correctamente.');
    }

    // ── Revocar beca ──────────────────────────────────────────────────────
    public function revocarBeca(BecaEstudiante $becaEstudiante)
    {
        $becaEstudiante->update(['activo' => false, 'fecha_fin' => today()]);

        return back()->with('success', 'Beca revocada.');
    }

    /* ════════════════════════════════════════════════════════════════════
     *  REPORTE PDF
     * ════════════════════════════════════════════════════════════════════ */

    public function reportePdf(Request $request)
    {
        $syActual = SchoolYear::actual();
        $mon      = Setting::get('payments_currency', 'DOP');

        // Cuota mensual base para estimar descuento
        $montoCuota = (float) Setting::get('payments_monto_cuota', 0);

        $becados = BecaEstudiante::with([
                'beca',
                'matricula.estudiante',
                'matricula.grupo.grado',
                'matricula.grupo.seccion',
            ])
            ->whereHas('matricula', fn($m) => $m->where('school_year_id', $syActual?->id))
            ->where('activo', true)
            ->get()
            ->groupBy('beca.tipo');

        $inst   = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $syActual ? \App\Models\BoletinConfig::getOrCreate($syActual->id) : null;

        // Calcular totales por tipo
        $resumen = [];
        foreach ($becados as $tipo => $grupo) {
            $totalDescuento = $grupo->sum(fn($be) => $be->beca?->calcularDescuento($montoCuota) ?? 0);
            $resumen[$tipo] = [
                'cantidad'       => $grupo->count(),
                'desc_mensual'   => $totalDescuento,
                'desc_anual'     => $totalDescuento * 12,
            ];
        }
        $totalBecados  = $becados->flatten()->count();
        $totalMensual  = collect($resumen)->sum('desc_mensual');
        $totalAnual    = collect($resumen)->sum('desc_anual');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.becas.reporte_pdf',
            compact('becados', 'resumen', 'inst', 'config', 'syActual', 'mon',
                    'montoCuota', 'totalBecados', 'totalMensual', 'totalAnual')
        )->setPaper('letter', 'portrait');

        return $pdf->download('becas_' . now()->format('Ymd') . '.pdf');
    }
}
