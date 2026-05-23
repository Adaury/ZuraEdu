<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarnetIdentidad;
use App\Models\CarnetZona;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Services\CarnetQrService;
use App\Services\CarnetRiskScoreService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CarnetController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $query = CarnetIdentidad::with(['user', 'matricula.grupo.grado', 'matricula.grupo.seccion'])
            ->where('tipo', 'estudiante');

        if ($search = $request->search) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($estado = $request->estado) {
            $query->where('estado', $estado);
        }

        $carnets = $query->latest()->paginate(20)->withQueryString();
        $zonas   = CarnetZona::activas()->orderBy('nombre')->get();

        return view('admin.carnet.index', compact('carnets', 'schoolYear', 'zonas'));
    }

    public function generarMasivo(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return back()->with('error', 'No hay año escolar activo.');
        }

        $matriculas = Matricula::with('estudiante.user')
            ->activas()
            ->where('school_year_id', $schoolYear->id)
            ->get();

        $creados = 0;
        foreach ($matriculas as $mat) {
            $user = $mat->estudiante?->user;
            if (! $user || ! $user->id) continue;

            $carnet = CarnetQrService::obtenerOCrear($user, 'estudiante', $mat->id);
            // Actualizar matricula_id si ya existía sin él
            if (! $carnet->matricula_id) {
                $carnet->update(['matricula_id' => $mat->id]);
            }
            $creados++;
        }

        return back()->with('success', "Se generaron/verificaron {$creados} carnets.");
    }

    public function show(CarnetIdentidad $carnet)
    {
        $carnet->load(['user', 'matricula.grupo.grado', 'matricula.grupo.seccion', 'accesos' => fn($q) => $q->latest()->limit(20)]);
        $risk = CarnetRiskScoreService::calcular($carnet);

        return view('admin.carnet.show', compact('carnet', 'risk'));
    }

    public function suspender(CarnetIdentidad $carnet)
    {
        $nuevo = $carnet->estado === 'activo' ? 'suspendido' : 'activo';
        $carnet->update(['estado' => $nuevo]);
        CarnetQrService::invalidarCache($carnet);

        return back()->with('success', "Carnet {$nuevo}.");
    }

    public function destroy(CarnetIdentidad $carnet)
    {
        CarnetQrService::invalidarCache($carnet);
        $carnet->delete();

        return back()->with('success', 'Carnet eliminado.');
    }

    // ── PDF de un carnet individual ───────────────────────────────────────────

    public function pdf(CarnetIdentidad $carnet)
    {
        $carnet->load(['user', 'matricula.grupo.grado', 'matricula.grupo.seccion']);
        $qrContent = CarnetQrService::qrContent($carnet);

        $pdf = Pdf::loadView('admin.carnet.pdf_carnet', compact('carnet', 'qrContent'))
            ->setPaper([0, 0, 226.77, 141.73], 'landscape'); // CR80 (85.6 × 53.98mm)

        return $pdf->stream("carnet-{$carnet->numero_carnet}.pdf");
    }

    // ── PDF masivo (grupo) ────────────────────────────────────────────────────

    public function pdfGrupo(Request $request)
    {
        $grupoId = $request->grupo_id;
        $carnets = CarnetIdentidad::with(['user', 'matricula.grupo.grado', 'matricula.grupo.seccion'])
            ->where('tipo', 'estudiante')
            ->whereHas('matricula', fn($q) => $q->where('grupo_id', $grupoId))
            ->activos()
            ->get();

        $pdf = Pdf::loadView('admin.carnet.pdf_grupo', compact('carnets'))
            ->setPaper('letter');

        return $pdf->stream('carnets-grupo.pdf');
    }

    // ── Zonas ─────────────────────────────────────────────────────────────────

    public function zonas()
    {
        $zonas = CarnetZona::orderBy('nombre')->get();
        return view('admin.carnet.zonas', compact('zonas'));
    }

    public function zonaStore(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:80',
            'tipo'   => 'required|in:porteria,biblioteca,comedor,laboratorio,salon,otro',
        ]);
        CarnetZona::create($data);
        return back()->with('success', 'Zona creada.');
    }

    public function zonaToggle(CarnetZona $zona)
    {
        $zona->update(['activo' => ! $zona->activo]);
        return back()->with('success', 'Zona actualizada.');
    }

    public function zonaDestroy(CarnetZona $zona)
    {
        $zona->delete();
        return back()->with('success', 'Zona eliminada.');
    }
}
