<?php

namespace App\Http\Controllers\Portal;

use App\Events\ClassroomMeetingUpdated;
use App\Http\Controllers\Controller;
use App\Models\ArchivoEntrega;
use App\Models\ArchivoMaterial;
use App\Models\ClassroomMessage;
use App\Models\ClaseVirtual;
use App\Models\CompetenciaEspecifica;
use App\Models\Docente;
use App\Models\EntregaClassroom;
use App\Models\MaterialClase;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\ZcRecurso;
use App\Models\ZcRubric;
use App\Models\ZcRubricCalificacion;
use App\Models\ZcRubricCriterio;
use App\Services\ZuraClassGradeSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClassroomDocenteController extends Controller
{
    /** Obtiene el docente autenticado o aborta */
    private function getDocente(): Docente
    {
        $docente = Docente::where('user_id', auth()->id())->first();
        abort_unless($docente, 403, 'No tiene perfil de docente.');
        return $docente;
    }

    /** Verifica que la clase pertenece al docente */
    private function autorizarClase(ClaseVirtual $clase, Docente $docente): void
    {
        abort_unless(
            $clase->asignacion->docente_id === $docente->id,
            403,
            'No tiene acceso a esta aula virtual.'
        );
    }

    // ── index ──────────────────────────────────────────────────────────────
    public function index()
    {
        $docente    = $this->getDocente();
        $schoolYear = SchoolYear::actual();

        $clases = ClaseVirtual::with(['asignacion.asignatura', 'asignacion.grupo', 'materiales'])
            ->whereHas('asignacion', function ($q) use ($docente, $schoolYear) {
                $q->where('docente_id', $docente->id)
                  ->where('activo', true)
                  ->when($schoolYear, fn($s) => $s->where('school_year_id', $schoolYear->id));
            })
            ->latest()
            ->get();

        return view('portal.classroom.docente.index', compact('clases'));
    }

    // ── show ───────────────────────────────────────────────────────────────
    public function show(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.grupo']);
        $this->autorizarClase($claseVirtual, $docente);

        $materiales = $claseVirtual->materiales()
            ->with(['archivos', 'entregas'])
            ->get();

        return view('portal.classroom.docente.show', compact('claseVirtual', 'materiales'));
    }

    // ── crearMaterial ─────────────────────────────────────────────────────
    public function crearMaterial(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion.asignatura');
        $this->autorizarClase($claseVirtual, $docente);

        $tipos      = MaterialClase::TIPOS;
        $schoolYear = $claseVirtual->asignacion->school_year_id;
        $periodos   = Periodo::where('school_year_id', $schoolYear)->orderBy('numero')->get();
        $competencias = CompetenciaEspecifica::where('asignatura_id', $claseVirtual->asignacion->asignatura_id)
            ->activas()->orderBy('nombre')->get();

        return view('portal.classroom.docente.crear_material',
            compact('claseVirtual', 'tipos', 'periodos', 'competencias'));
    }

    // ── guardarMaterial ───────────────────────────────────────────────────
    public function guardarMaterial(Request $request, ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $data = $request->validate([
            'titulo'            => 'required|string|max:200',
            'tipo'              => 'required|in:anuncio,material,tarea,evaluacion',
            'subtipo'           => 'nullable|string|max:30',
            'contenido'         => 'nullable|string',
            'url_externo'       => 'nullable|url|max:300',
            'fecha_limite'      => 'nullable|date',
            'puntos'            => 'nullable|integer|min:0|max:100',
            'publicado'         => 'boolean',
            'permite_reentrega' => 'boolean',
            'limite_tiempo'     => 'nullable|integer|min:1|max:300',
            'publicar_en'       => 'nullable|date',
            'periodo_id'        => 'nullable|exists:periodos,id',
            'competencia_id'    => 'nullable|exists:competencias_especificas,id',
            'archivos.*'        => 'nullable|file|max:10240',
        ]);

        $data['clase_virtual_id']  = $claseVirtual->id;
        $data['publicado']         = $request->boolean('publicado', true);
        $data['permite_reentrega'] = $request->boolean('permite_reentrega');
        $data['orden']             = $claseVirtual->materiales()->max('orden') + 1;

        $material = MaterialClase::create($data);

        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $file) {
                $ruta = $file->store("classroom/{$claseVirtual->id}", 'public');
                ArchivoMaterial::create([
                    'material_id'    => $material->id,
                    'nombre_original'=> $file->getClientOriginalName(),
                    'ruta'           => $ruta,
                    'tipo_mime'      => $file->getMimeType(),
                ]);
            }
        }

        // Crear rúbrica si se enviaron criterios
        if ($request->filled('rubric_nombre') && $request->has('criterios')) {
            $rubric = ZcRubric::create([
                'material_id' => $material->id,
                'nombre'      => $request->rubric_nombre,
                'descripcion' => $request->rubric_descripcion,
            ]);
            foreach ($request->criterios as $i => $criterio) {
                if (empty($criterio['nombre'])) continue;
                ZcRubricCriterio::create([
                    'rubric_id'   => $rubric->id,
                    'nombre'      => $criterio['nombre'],
                    'descripcion' => $criterio['descripcion'] ?? null,
                    'puntaje_max' => $criterio['puntaje_max'] ?? 10,
                    'orden'       => $i,
                ]);
            }
        }

        // Notificar a estudiantes si el material está publicado
        if ($material->publicado && !$material->publicar_en) {
            $this->notificarEstudiantesNuevoMaterial($claseVirtual, $material);
        }

        return redirect()->route('portal.docente.classroom.show', $claseVirtual)
            ->with('success', 'Material publicado correctamente.');
    }

    // ── Notificar a estudiantes del grupo ────────────────────────────────
    private function notificarEstudiantesNuevoMaterial(ClaseVirtual $claseVirtual, MaterialClase $material): void
    {
        try {
            $matriculas = \App\Models\Matricula::with('estudiante.user')
                ->where('grupo_id', $claseVirtual->asignacion->grupo_id)
                ->where('school_year_id', $claseVirtual->asignacion->school_year_id)
                ->where('estado', 'activa')
                ->get();

            $tipo = match($material->tipo) {
                'tarea'      => 'zura_tarea',
                'evaluacion' => 'zura_quiz',
                'anuncio'    => 'zura_anuncio',
                default      => 'zura_material',
            };

            $titulo = match($material->tipo) {
                'tarea'      => 'Nueva tarea asignada',
                'evaluacion' => 'Nueva evaluación disponible',
                'anuncio'    => 'Nuevo anuncio en '.$claseVirtual->nombre,
                default      => 'Nuevo material publicado',
            };

            $userIds = $matriculas
                ->filter(fn($m) => $m->estudiante?->user_id)
                ->pluck('estudiante.user_id')
                ->unique()
                ->filter()
                ->toArray();

            if (!empty($userIds)) {
                \App\Models\Notificacion::enviarA(
                    $userIds, $tipo, $titulo,
                    $material->titulo . ' — ' . ($claseVirtual->asignacion->asignatura?->nombre ?? $claseVirtual->nombre)
                );
            }
        } catch (\Exception $e) {
            // Silenciar: las notificaciones no deben romper el flujo principal
        }
    }

    // ── editarMaterial ────────────────────────────────────────────────────
    public function editarMaterial(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion.asignatura');
        $this->autorizarClase($claseVirtual, $docente);

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);
        $material->load('archivos');
        $tipos = MaterialClase::TIPOS;

        return view('portal.classroom.docente.editar_material', compact('claseVirtual', 'material', 'tipos'));
    }

    // ── actualizarMaterial ────────────────────────────────────────────────
    public function actualizarMaterial(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);

        $data = $request->validate([
            'titulo'       => 'required|string|max:200',
            'tipo'         => 'required|in:anuncio,material,tarea,evaluacion',
            'contenido'    => 'nullable|string',
            'url_externo'  => 'nullable|url|max:300',
            'fecha_limite' => 'nullable|date',
            'puntos'       => 'nullable|integer|min:0|max:100',
            'publicado'    => 'boolean',
            'archivos.*'   => 'nullable|file|max:10240',
        ]);

        $data['publicado'] = $request->boolean('publicado');
        $material->update($data);

        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $file) {
                $ruta = $file->store("classroom/{$claseVirtual->id}", 'public');
                ArchivoMaterial::create([
                    'material_id'    => $material->id,
                    'nombre_original'=> $file->getClientOriginalName(),
                    'ruta'           => $ruta,
                    'tipo_mime'      => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('portal.docente.classroom.show', $claseVirtual)
            ->with('success', 'Material actualizado.');
    }

    // ── eliminarMaterial ──────────────────────────────────────────────────
    public function eliminarMaterial(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);

        // Eliminar archivos del disco
        foreach ($material->archivos as $archivo) {
            Storage::disk('public')->delete($archivo->ruta);
        }

        $material->delete();

        return back()->with('success', 'Material eliminado.');
    }

    // ── verEntregas ───────────────────────────────────────────────────────
    public function verEntregas(ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.grupo']);
        $this->autorizarClase($claseVirtual, $docente);

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);

        // Matriculados en el grupo de la asignación
        $matriculas = \App\Models\Matricula::with(['estudiante', 'grupo'])
            ->where('grupo_id', $claseVirtual->asignacion->grupo_id)
            ->where('school_year_id', $claseVirtual->asignacion->school_year_id)
            ->where('estado', 'activa')
            ->orderBy('id')
            ->get();

        $entregas = EntregaClassroom::where('material_id', $material->id)
            ->with('matricula.estudiante')
            ->get()
            ->keyBy('matricula_id');

        return view('portal.classroom.docente.entregas', compact(
            'claseVirtual', 'material', 'matriculas', 'entregas'
        ));
    }

    // ── verEntregaDetalle ─────────────────────────────────────────────────
    public function verEntregaDetalle(ClaseVirtual $claseVirtual, MaterialClase $material, EntregaClassroom $entrega)
    {
        $docente = $this->getDocente();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.grupo']);
        $this->autorizarClase($claseVirtual, $docente);

        $entrega->load(['matricula.estudiante', 'archivos', 'rubricCalificaciones.criterio']);
        $material->load(['rubric.criterios', 'archivos']);

        // Contexto: anterior/siguiente entrega
        $matriculas = \App\Models\Matricula::where('grupo_id', $claseVirtual->asignacion->grupo_id)
            ->where('school_year_id', $claseVirtual->asignacion->school_year_id)
            ->where('estado', 'activa')->pluck('id')->toArray();

        return view('portal.classroom.docente.entrega_detalle',
            compact('claseVirtual', 'material', 'entrega', 'matriculas'));
    }

    // ── calificarEntrega ──────────────────────────────────────────────────
    public function calificarEntrega(Request $request, ClaseVirtual $claseVirtual, EntregaClassroom $entrega)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $data = $request->validate([
            'calificacion'       => 'required|numeric|min:0|max:100',
            'comentario_docente' => 'nullable|string|max:1000',
            'retroalimentacion'  => 'nullable|string|max:2000',
            'devolver'           => 'boolean',
            'sincronizar_notas'  => 'boolean',
            // Rúbrica
            'rubrica'            => 'nullable|array',
            'rubrica.*.criterio_id' => 'exists:zc_rubric_criterios,id',
            'rubrica.*.puntaje'     => 'numeric|min:0',
            'rubrica.*.comentario'  => 'nullable|string',
        ]);

        // Si viene rúbrica, calcular nota desde criterios
        if (!empty($data['rubrica'])) {
            $totalRubric = 0;
            foreach ($data['rubrica'] as $item) {
                ZcRubricCalificacion::updateOrCreate(
                    ['entrega_id' => $entrega->id, 'criterio_id' => $item['criterio_id']],
                    ['puntaje' => $item['puntaje'], 'comentario' => $item['comentario'] ?? null]
                );
                $totalRubric += $item['puntaje'];
            }
            $maxRubric = ZcRubricCriterio::whereIn('id', collect($data['rubrica'])->pluck('criterio_id'))
                ->sum('puntaje_max');
            if ($maxRubric > 0) {
                $data['calificacion'] = round(($totalRubric / $maxRubric) * ($entrega->material->puntos ?? 100), 2);
            }
        }

        $estado = $request->boolean('devolver') ? 'devuelto' : 'calificado';

        $entrega->update([
            'calificacion'       => $data['calificacion'],
            'comentario_docente' => $data['comentario_docente'] ?? null,
            'retroalimentacion'  => $data['retroalimentacion'] ?? null,
            'devuelta'           => $request->boolean('devolver'),
            'estado'             => $estado,
            'fecha_revision'     => now(),
            'revisado_por'       => auth()->id(),
        ]);

        // Sincronizar con libro de notas
        if ($request->boolean('sincronizar_notas') && $estado === 'calificado') {
            app(ZuraClassGradeSync::class)->sincronizar($entrega);
        }

        // Notificar al estudiante
        try {
            $estudianteUserId = $entrega->matricula?->estudiante?->user_id;
            if ($estudianteUserId) {
                $tipoNotif = $estado === 'devuelto' ? 'zura_devuelto' : 'zura_calificado';
                $tituloNotif = $estado === 'devuelto'
                    ? 'Tarea devuelta para corrección'
                    : 'Tarea calificada';
                \App\Models\Notificacion::enviar(
                    $estudianteUserId,
                    $tipoNotif,
                    $tituloNotif,
                    $entrega->material?->titulo . ' — ' . ($data['calificacion'] ?? '') . ($estado === 'calificado' ? ' pts' : '')
                );
            }
        } catch (\Exception $e) { }

        return back()->with('success', $estado === 'devuelto'
            ? 'Entrega devuelta al estudiante para corrección.'
            : 'Entrega calificada correctamente.');
    }

    // ── devolverEntrega ───────────────────────────────────────────────────
    public function devolverEntrega(Request $request, ClaseVirtual $claseVirtual, EntregaClassroom $entrega)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $entrega->update([
            'estado'            => 'devuelto',
            'devuelta'          => true,
            'comentario_docente'=> $request->input('comentario_docente'),
            'fecha_revision'    => now(),
            'revisado_por'      => auth()->id(),
        ]);

        return back()->with('success', 'Entrega devuelta para corrección.');
    }

    // ── subirArchivo (AJAX) ────────────────────────────────────────────────
    public function subirArchivo(Request $request, ClaseVirtual $claseVirtual, MaterialClase $material)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        abort_unless($material->clase_virtual_id === $claseVirtual->id, 404);

        $request->validate(['archivo' => 'required|file|max:10240']);

        $file = $request->file('archivo');
        $ruta = $file->store("classroom/{$claseVirtual->id}", 'public');

        $archivo = ArchivoMaterial::create([
            'material_id'    => $material->id,
            'nombre_original'=> $file->getClientOriginalName(),
            'ruta'           => $ruta,
            'tipo_mime'      => $file->getMimeType(),
        ]);

        return response()->json([
            'id'             => $archivo->id,
            'nombre_original'=> $archivo->nombre_original,
            'url'            => $archivo->url,
        ]);
    }

    // ── eliminarArchivo ───────────────────────────────────────────────────
    public function eliminarArchivo(ClaseVirtual $claseVirtual, ArchivoMaterial $archivo)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        Storage::disk('public')->delete($archivo->ruta);
        $archivo->delete();

        return back()->with('success', 'Archivo eliminado.');
    }

    // ── Personas ──────────────────────────────────────────────────────────
    public function personas(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.grupo', 'asignacion.docente']);
        $this->autorizarClase($claseVirtual, $docente);

        $matriculas = \App\Models\Matricula::with('estudiante.user')
            ->where('grupo_id', $claseVirtual->asignacion->grupo_id)
            ->where('school_year_id', $claseVirtual->asignacion->school_year_id)
            ->where('estado', 'activa')
            ->orderBy('id')
            ->get();

        return view('portal.classroom.docente.personas', compact('claseVirtual', 'matriculas'));
    }

    // ── Calificaciones resumen ────────────────────────────────────────────
    public function calificacionesResumen(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.grupo']);
        $this->autorizarClase($claseVirtual, $docente);

        $materiales = $claseVirtual->materiales()
            ->whereIn('tipo', ['tarea', 'evaluacion'])
            ->with(['entregas.matricula.estudiante'])
            ->get();

        $matriculas = \App\Models\Matricula::with('estudiante')
            ->where('grupo_id', $claseVirtual->asignacion->grupo_id)
            ->where('school_year_id', $claseVirtual->asignacion->school_year_id)
            ->where('estado', 'activa')
            ->orderBy('id')
            ->get();

        return view('portal.classroom.docente.calificaciones',
            compact('claseVirtual', 'materiales', 'matriculas'));
    }

    // ── Recursos ──────────────────────────────────────────────────────────
    public function recursos(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load(['asignacion.asignatura', 'asignacion.grupo']);
        $this->autorizarClase($claseVirtual, $docente);

        $recursos = ZcRecurso::where('clase_virtual_id', $claseVirtual->id)
            ->orderBy('orden')->orderByDesc('created_at')->get();

        return view('portal.classroom.docente.recursos', compact('claseVirtual', 'recursos'));
    }

    public function guardarRecurso(Request $request, ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $data = $request->validate([
            'titulo'    => 'required|string|max:200',
            'tipo'      => 'required|in:pdf,video,enlace,imagen,presentacion,otro',
            'descripcion'=> 'nullable|string|max:500',
            'url'       => 'nullable|url|max:500',
            'archivo'   => 'nullable|file|max:20480',
        ]);

        $data['clase_virtual_id'] = $claseVirtual->id;
        $data['creado_por']       = auth()->id();

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $data['ruta_archivo']  = $file->store("classroom/{$claseVirtual->id}/recursos", 'public');
            $data['nombre_archivo'] = $file->getClientOriginalName();
        }

        ZcRecurso::create($data);

        return back()->with('success', 'Recurso agregado correctamente.');
    }

    public function eliminarRecurso(ClaseVirtual $claseVirtual, ZcRecurso $recurso)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        if ($recurso->ruta_archivo) {
            Storage::disk('public')->delete($recurso->ruta_archivo);
        }
        $recurso->delete();

        return back()->with('success', 'Recurso eliminado.');
    }

    // ── Generar código de clase ───────────────────────────────────────────
    public function generarCodigo(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $codigo = $claseVirtual->generarCodigo();

        return response()->json(['codigo' => $codigo]);
    }

    // ── Sincronizar notas completo ────────────────────────────────────────
    public function sincronizarNotas(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $sync = app(ZuraClassGradeSync::class);
        $materiales = $claseVirtual->materiales()
            ->whereIn('tipo', ['tarea', 'evaluacion'])
            ->whereNotNull('periodo_id')
            ->get();

        $count = 0;
        foreach ($materiales as $material) {
            $sync->recalcularPromedioGrupo($material);
            $count++;
        }

        return back()->with('success', "Notas sincronizadas para {$count} actividades.");
    }

    // ── Videoconferencia: iniciar clase en vivo ───────────────────────────

    public function iniciarMeeting(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $roomName = 'zuraclass-' . $claseVirtual->id . '-' . Str::random(6);
        $jitsiBase = config('services.jitsi.domain', 'meet.jit.si');
        $meetingUrl = "https://{$jitsiBase}/{$roomName}";

        $claseVirtual->update([
            'meeting_url'        => $meetingUrl,
            'meeting_status'     => 'active',
            'meeting_started_at' => now(),
        ]);

        broadcast(new ClassroomMeetingUpdated($claseVirtual->id, 'active', $meetingUrl));

        return response()->json([
            'meeting_url' => $meetingUrl,
            'status'      => 'active',
        ]);
    }

    public function terminarMeeting(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $claseVirtual->update([
            'meeting_status' => 'idle',
        ]);

        broadcast(new ClassroomMeetingUpdated($claseVirtual->id, 'idle', null));

        return response()->json(['status' => 'idle']);
    }

    // ── Chat: mensajes fijados del aula ───────────────────────────────────

    public function mensajesFijados(ClaseVirtual $claseVirtual)
    {
        $docente = $this->getDocente();
        $claseVirtual->load('asignacion');
        $this->autorizarClase($claseVirtual, $docente);

        $fijados = ClassroomMessage::where('clase_virtual_id', $claseVirtual->id)
            ->where('fijado', true)
            ->with('user')
            ->latest()
            ->get();

        return response()->json($fijados);
    }
}
