<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class VerificacionMatriculaController extends Controller
{
    public function index(Request $request)
    {
        $resultado = null;
        if ($request->filled('q')) {
            $resultado = $this->buscarPorTermino(trim($request->q));
        }
        return view('verificacion.matricula', compact('resultado'));
    }

    public function buscar(Request $request)
    {
        $request->validate([
            'busqueda' => 'required|string|min:2|max:80',
        ]);

        $resultado = $this->buscarPorTermino(trim($request->busqueda));
        return back()->with('resultado', $resultado)->withInput();
    }

    private function buscarPorTermino(string $term): array
    {
        $sy = SchoolYear::actual();

        $estudiante = Estudiante::where('cedula', $term)
            ->orWhere('numero_matricula', $term)
            ->orWhere(function ($q) use ($term) {
                $q->where('nombres', 'LIKE', "%{$term}%")
                  ->orWhere('apellidos', 'LIKE', "%{$term}%")
                  ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("CONCAT(apellidos, ', ', nombres) LIKE ?", ["%{$term}%"]);
            })
            ->first();

        if (! $estudiante) {
            return [
                'encontrado' => false,
                'busqueda'   => $term,
                'msg'        => 'No se encontró ningún estudiante con esos datos.',
            ];
        }

        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'grupo.tutor', 'schoolYear'])
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()
            ->first();

        // Si no hay matrícula en el año actual, buscar cualquier matrícula activa
        if (! $matricula) {
            $matricula = $estudiante->matriculas()
                ->with(['grupo.grado', 'grupo.seccion', 'grupo.tutor', 'schoolYear'])
                ->where('estado', 'activa')
                ->latest()
                ->first();
        }

        // Si tampoco hay ninguna activa, buscar la última matrícula sin importar estado
        if (! $matricula) {
            $matricula = $estudiante->matriculas()
                ->with(['grupo.grado', 'grupo.seccion', 'grupo.tutor', 'schoolYear'])
                ->latest()
                ->first();
        }

        $esAnioActual = $sy && $matricula?->school_year_id === $sy->id;
        $advertencia  = null;
        if ($matricula && ! $esAnioActual) {
            $advertencia = 'El estudiante no tiene matrícula activa en el año escolar actual. Se muestra la matrícula más reciente.';
        } elseif (! $matricula) {
            $advertencia = 'El estudiante no tiene ninguna matrícula registrada.';
        }

        return [
            'encontrado'     => true,
            'nombre'         => $estudiante->nombre_completo ?? trim($estudiante->nombres . ' ' . $estudiante->apellidos),
            'cedula'         => $estudiante->cedula ?? '—',
            'matricula_num'  => $estudiante->numero_matricula ?? '—',
            'foto_url'       => $estudiante->foto_url ?? null,
            'grado'          => $matricula?->grupo?->grado?->nombre ?? '—',
            'seccion'        => $matricula?->grupo?->seccion?->nombre ?? '—',
            'year'           => $matricula?->schoolYear?->nombre ?? '—',
            'estado'         => $matricula?->estado ?? '—',
            'tutor'          => $matricula?->grupo?->tutor?->name ?? '—',
            'fecha_matricula' => $matricula?->fecha_matricula?->format('d/m/Y') ?? '—',
            'numero_orden'   => $matricula?->numero_orden ?? '—',
            'advertencia'    => $advertencia,
            'busqueda'       => $term,
        ];
    }
}
