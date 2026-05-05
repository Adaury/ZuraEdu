<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class VerificacionMatriculaController extends Controller
{
    public function index()
    {
        return view('verificacion.matricula');
    }

    public function buscar(Request $request)
    {
        $request->validate([
            'busqueda' => 'required|string|min:3|max:50',
        ]);

        $term = trim($request->busqueda);
        $sy   = SchoolYear::actual();

        // Buscar por cédula o número de matrícula
        $estudiante = Estudiante::where('cedula', $term)
            ->orWhere('matricula', $term)
            ->first();

        if (! $estudiante) {
            return back()->with('resultado', [
                'encontrado' => false,
                'busqueda'   => $term,
            ]);
        }

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()
            ->first();

        if (! $matricula) {
            return back()->with('resultado', [
                'encontrado' => false,
                'busqueda'   => $term,
                'msg'        => 'El estudiante existe pero no tiene matrícula activa en el año escolar actual.',
            ]);
        }

        return back()->with('resultado', [
            'encontrado'    => true,
            'nombre'        => $estudiante->nombre_completo ?? trim($estudiante->nombres . ' ' . $estudiante->apellidos),
            'matricula_num' => $estudiante->matricula ?? '—',
            'grado'         => $matricula->grupo?->grado?->nombre ?? '—',
            'seccion'       => $matricula->grupo?->seccion?->nombre ?? '—',
            'year'          => $matricula->schoolYear?->nombre ?? '—',
            'estado'        => $matricula->estado,
        ]);
    }
}
