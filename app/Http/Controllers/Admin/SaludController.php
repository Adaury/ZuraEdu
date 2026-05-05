<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigInstitucional;
use App\Models\Estudiante;
use App\Models\FichaSalud;
use App\Models\IncidenteMedico;
use Illuminate\Http\Request;

class SaludController extends Controller
{
    // ── Ficha de Salud ────────────────────────────────────────────────────

    /**
     * Mostrar / editar ficha de salud de un estudiante.
     */
    public function ficha(Estudiante $estudiante)
    {
        $ficha      = FichaSalud::firstOrNew(['estudiante_id' => $estudiante->id]);
        $tiposSangre = FichaSalud::TIPOS_SANGRE;
        $incidentes  = $estudiante->incidentesMedicos()
                                  ->latest('fecha')
                                  ->take(10)
                                  ->get();

        return view('admin.salud.ficha', compact('estudiante', 'ficha', 'tiposSangre', 'incidentes'));
    }

    /**
     * Guardar (crear o actualizar) ficha de salud.
     */
    public function guardarFicha(Request $request, Estudiante $estudiante)
    {
        $data = $request->validate([
            'tipo_sangre'         => 'nullable|string|max:5',
            'alergias'            => 'nullable|string|max:2000',
            'condiciones_medicas' => 'nullable|string|max:2000',
            'medicamentos'        => 'nullable|string|max:2000',
            'contacto_emergencia' => 'nullable|string|max:150',
            'telefono_emergencia' => 'nullable|string|max:30',
            'seguro_medico'       => 'nullable|string|max:100',
            'num_seguro'          => 'nullable|string|max:60',
        ]);

        FichaSalud::updateOrCreate(
            ['estudiante_id' => $estudiante->id],
            $data
        );

        return redirect()
            ->route('admin.salud.ficha', $estudiante)
            ->with('success', 'Ficha de salud actualizada correctamente.');
    }

    // ── Incidentes Médicos ────────────────────────────────────────────────

    /**
     * Listado de incidentes con filtros.
     */
    public function incidentes(Request $request)
    {
        $query = IncidenteMedico::with('estudiante')->latest('fecha');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estudiante_id')) {
            $query->where('estudiante_id', $request->estudiante_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('descripcion', 'like', "%{$q}%")
                   ->orWhere('accion_tomada', 'like', "%{$q}%")
                   ->orWhere('remitido_a', 'like', "%{$q}%")
                   ->orWhereHas('estudiante', fn($s) =>
                       $s->where('nombres', 'like', "%{$q}%")
                         ->orWhere('apellidos', 'like', "%{$q}%")
                   );
            });
        }

        $incidentes  = $query->paginate(25)->withQueryString();
        $tipos       = IncidenteMedico::TIPOS;
        $estudiantes = Estudiante::activos()->orderBy('apellidos')->get();

        // Conteos por tipo para tarjetas resumen
        $conteosTipo = IncidenteMedico::selectRaw('tipo, count(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        return view('admin.salud.incidentes', compact(
            'incidentes', 'tipos', 'estudiantes', 'conteosTipo'
        ));
    }

    /**
     * Formulario para registrar un incidente.
     */
    public function crearIncidente(Request $request)
    {
        $estudianteId = $request->estudiante_id;
        $estudiante   = $estudianteId ? Estudiante::find($estudianteId) : null;
        $estudiantes  = Estudiante::activos()->orderBy('apellidos')->get();
        $tipos        = IncidenteMedico::TIPOS;

        return view('admin.salud.incidente_create', compact('estudiantes', 'tipos', 'estudiante'));
    }

    /**
     * Almacenar incidente médico.
     */
    public function guardarIncidente(Request $request)
    {
        $data = $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'fecha'         => 'required|date',
            'tipo'          => 'required|in:accidente,enfermedad,alergia,otro',
            'descripcion'   => 'required|string|max:3000',
            'accion_tomada' => 'required|string|max:3000',
            'remitido_a'    => 'nullable|string|max:150',
        ]);

        IncidenteMedico::create($data);

        return redirect()
            ->route('admin.salud.incidentes')
            ->with('success', 'Incidente médico registrado correctamente.');
    }

    /**
     * Eliminar un incidente médico.
     */
    public function eliminarIncidente(IncidenteMedico $incidente)
    {
        $incidente->delete();

        return back()->with('success', 'Incidente eliminado.');
    }

    // ── PDF Ficha Médica ──────────────────────────────────────────────────

    /**
     * Generar PDF de la ficha médica completa del estudiante.
     */
    public function fichaPdf(Estudiante $estudiante)
    {
        $ficha      = FichaSalud::where('estudiante_id', $estudiante->id)->first();
        $incidentes = $estudiante->incidentesMedicos()->latest('fecha')->get();
        $inst       = ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config     = ConfigInstitucional::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.salud.ficha_pdf',
            compact('estudiante', 'ficha', 'incidentes', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        $nombre = 'ficha_salud_' . str_replace([' ', ','], '_', strtolower($estudiante->nombre_completo))
                  . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
