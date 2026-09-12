<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\SchoolYear;
use App\Services\ActaFinalService;
use Illuminate\Support\Str;

class ActaFinalController extends Controller
{
    public function __construct(private ActaFinalService $acta) {}

    /**
     * Verifica acceso y arma los datos comunes a ver()/pdf(). Aborta 404 si
     * el grupo no es de Primer Ciclo, 403 si el usuario no puede verlo.
     */
    private function cargar(Grupo $grupo): array
    {
        $grupo->load(['grado', 'seccion', 'schoolYear', 'tutor']);

        // Solo Primer Ciclo por ahora -- Segundo Ciclo (4to-6to) tiene una
        // estructura de asignaturas distinta y queda para una fase posterior.
        if (! $grupo->grado || ! $grupo->grado->esPrimerCiclo()) {
            abort(404, 'El Acta Final oficial solo está disponible para grupos de Primer Ciclo por ahora.');
        }

        if (! $this->acta->puedeVerGrupo($grupo)) {
            abort(403);
        }

        $schoolYear = $grupo->schoolYear ?? SchoolYear::find($grupo->school_year_id) ?? SchoolYear::actual();
        abort_if(! $schoolYear, 404, 'No hay año escolar asociado a este grupo.');

        ['asignaciones' => $asignaciones, 'filas' => $filas] = $this->acta->construirActaGrupo($grupo, $schoolYear);

        $inst = [
            'nombre_institucion' => \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name')),
            'codigo_centro'      => \App\Models\ConfigInstitucional::get('codigo_centro', ''),
            'regional'           => \App\Models\ConfigInstitucional::get('regional', ''),
            'distrito'           => \App\Models\ConfigInstitucional::get('distrito', ''),
            'tanda'              => \App\Models\ConfigInstitucional::get('tanda', ''),
            'sector'             => \App\Models\ConfigInstitucional::get('sector', ''),
            'zona'               => \App\Models\ConfigInstitucional::get('zona', ''),
            'director_distrito'  => \App\Models\ConfigInstitucional::get('director_distrito', ''),
            'nombre_director'    => \App\Models\ConfigInstitucional::get('nombre_director', ''),
            'secretario_docente' => \App\Models\ConfigInstitucional::get('secretario_docente', ''),
        ];

        return compact('grupo', 'schoolYear', 'asignaciones', 'filas', 'inst');
    }

    /**
     * Vista web interactiva -- Completivo/Extraordinario editables inline
     * (nota_cc/nota_ce, guardado vía CalificacionAcademicaController::guardarCelda).
     * "Final de Año" y "Especial" quedan de solo lectura (vienen de otras
     * pantallas: períodos y Planilla Académica respectivamente).
     */
    public function ver(Grupo $grupo)
    {
        $data = $this->cargar($grupo);

        $user = auth()->user();
        $docente = $user->tieneRolDocente()
            ? (Docente::where('user_id', $user->id)->first() ?? Docente::where('email', $user->email)->first())
            : null;

        // Admin/Coordinación (docente=null aquí) edita todas las columnas;
        // un docente solo la(s) asignatura(s) que él mismo imparte.
        $asignacionesEditables = $docente
            ? $data['asignaciones']->where('docente_id', $docente->id)->pluck('id')->all()
            : $data['asignaciones']->pluck('id')->all();

        // Un solo endpoint (POST, asignacion_id va en el body) -- misma URL
        // repetida para cada columna, a diferencia del portal docente donde
        // cada asignación tiene su propia ruta PATCH.
        $urlUnica = route('admin.calificaciones.guardar-celda-academica');
        $guardarUrlPorAsignacion = $data['asignaciones']->mapWithKeys(fn ($a) => [$a->id => $urlUnica])->all();

        return view('admin.acta_final.ver', $data + [
            'asignacionesEditables'     => $asignacionesEditables,
            'guardarUrlPorAsignacion'   => $guardarUrlPorAsignacion,
            'metodoGuardado'            => 'POST',
            'pdfUrl'                    => route('admin.acta-final.pdf', $grupo->id),
            // Los datos institucionales (nombre del centro, tanda, sector, etc.)
            // son datos del CENTRO, no de un grupo/materia -- solo quien puede
            // administrarlos en /admin/sistema puede editarlos aquí también.
            'puedeEditarInstitucional' => $user->can('solo-administrador'),
            'guardarInstitucionalUrl'  => route('admin.sistema.institucional.guardar-campo'),
        ]);
    }

    public function pdf(Grupo $grupo)
    {
        $data = $this->cargar($grupo);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.acta_final.pdf', $data)
            ->setPaper('letter', 'landscape');

        return $pdf->download('acta_final_' . Str::slug($grupo->nombre_completo ?? $grupo->id) . '.pdf');
    }
}
