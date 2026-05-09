<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\InscripcionEvento;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Evento::withCount('inscripciones');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_inicio', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_inicio', '<=', $request->fecha_hasta);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === '1');
        }

        if ($request->filled('q')) {
            $query->where('nombre', 'like', '%' . $request->q . '%');
        }

        $eventos = $query->orderByDesc('fecha_inicio')->paginate(20)->withQueryString();

        return view('admin.eventos.index', compact('eventos'));
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function create()
    {
        return view('admin.eventos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'tipo'         => 'required|in:academico,deportivo,cultural,social,otro',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'lugar'        => 'nullable|string|max:255',
            'cupo_maximo'  => 'nullable|integer|min:1',
            'activo'       => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $evento = Evento::create($data);

        if ($evento->activo) {
            try {
                $fecha   = $evento->fecha_inicio->format('d/m/Y');
                $titulo  = "🎉 Nuevo evento: {$evento->nombre}";
                $mensaje = "Se ha publicado un nuevo evento para el {$fecha}" . ($evento->lugar ? " en {$evento->lugar}" : '') . '.';
                $userIds = User::where('activo', true)->pluck('id')->toArray();
                if (!empty($userIds)) {
                    Notificacion::enviarA($userIds, 'general', $titulo, $mensaje, ['evento_id' => $evento->id]);
                }
            } catch (\Throwable) {}
        }

        return redirect()->route('admin.eventos.index')
                         ->with('success', 'Evento creado correctamente.');
    }

    // ── Show ──────────────────────────────────────────────────────────────

    public function show(Request $request, Evento $evento)
    {
        $evento->loadCount('inscripciones');

        $inscripciones = $evento->inscripciones()
            ->with('estudiante.matriculas.grupo.grado', 'estudiante.matriculas.grupo.seccion')
            ->get()
            ->sortBy('estudiante.nombre_completo');

        // Estudiantes del año activo para el buscador de inscripción
        $sy = SchoolYear::actual();

        $estudiantesQuery = Estudiante::activos()
            ->whereNotIn('id', $inscripciones->pluck('estudiante_id'));

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $estudiantesQuery->where(function ($q) use ($buscar) {
                $q->where('nombres',  'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('numero_matricula', 'like', "%{$buscar}%");
            });
        }

        $estudiantesDisponibles = $estudiantesQuery->orderBy('apellidos')->limit(50)->get();

        // Grupos del año activo para inscripción masiva
        $grupos = $sy
            ? Grupo::with(['grado', 'seccion'])
                   ->where('school_year_id', $sy->id)
                   ->activos()
                   ->get()
            : collect();

        return view('admin.eventos.show', compact(
            'evento', 'inscripciones', 'estudiantesDisponibles', 'grupos'
        ));
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function edit(Evento $evento)
    {
        return view('admin.eventos.create', compact('evento'));
    }

    public function update(Request $request, Evento $evento)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'tipo'         => 'required|in:academico,deportivo,cultural,social,otro',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'lugar'        => 'nullable|string|max:255',
            'cupo_maximo'  => 'nullable|integer|min:1',
            'activo'       => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $evento->update($data);

        return redirect()->route('admin.eventos.index')
                         ->with('success', 'Evento actualizado correctamente.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(Evento $evento)
    {
        $evento->delete();
        return back()->with('success', 'Evento eliminado.');
    }

    // ── Toggle activo ─────────────────────────────────────────────────────

    public function toggleActivo(Evento $evento)
    {
        $evento->update(['activo' => !$evento->activo]);
        $estado = $evento->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Evento {$estado}.");
    }

    // ── Inscribir estudiantes (batch por grupo o individual) ──────────────

    public function inscribir(Request $request, Evento $evento)
    {
        $request->validate([
            'estudiante_ids'   => 'nullable|array',
            'estudiante_ids.*' => 'exists:estudiantes,id',
            'grupo_id'         => 'nullable|exists:grupos,id',
        ]);

        $ids = collect($request->estudiante_ids ?? []);

        // Si viene grupo_id, sumar estudiantes de ese grupo
        if ($request->filled('grupo_id')) {
            $sy = SchoolYear::actual();
            if ($sy) {
                $idsGrupo = Matricula::where('grupo_id', $request->grupo_id)
                    ->where('school_year_id', $sy->id)
                    ->where('estado', 'activa')
                    ->pluck('estudiante_id');
                $ids = $ids->merge($idsGrupo)->unique();
            }
        }

        if ($ids->isEmpty()) {
            return back()->with('warning', 'No se seleccionó ningún estudiante.');
        }

        // Verificar cupo
        if (!is_null($evento->cupo_maximo)) {
            $actuales = $evento->inscripciones()->count();
            $disponible = $evento->cupo_maximo - $actuales;
            if ($ids->count() > $disponible) {
                return back()->with('error', "Solo hay {$disponible} cupo(s) disponibles para este evento.");
            }
        }

        $hoy = now()->toDateString();
        $insertados = 0;

        $estudiantesInscritos = collect();

        foreach ($ids as $estudianteId) {
            $existe = InscripcionEvento::where('evento_id', $evento->id)
                ->where('estudiante_id', $estudianteId)
                ->exists();

            if (!$existe) {
                InscripcionEvento::create([
                    'evento_id'        => $evento->id,
                    'estudiante_id'    => $estudianteId,
                    'fecha_inscripcion' => $hoy,
                    'asistio'          => false,
                ]);
                $insertados++;
                $estudiantesInscritos->push($estudianteId);
            }
        }

        if ($estudiantesInscritos->isNotEmpty()) {
            try {
                $fecha   = $evento->fecha_inicio->format('d/m/Y');
                $titulo  = "📋 Inscripción a evento: {$evento->nombre}";
                $mensaje = "Has sido inscrito/a en el evento «{$evento->nombre}» programado para el {$fecha}.";
                $estudiantes = \App\Models\Estudiante::with('representantes')
                    ->whereIn('id', $estudiantesInscritos)->get();
                $userIds = [];
                foreach ($estudiantes as $est) {
                    if ($est->user_id) $userIds[] = $est->user_id;
                    foreach ($est->representantes as $rep) {
                        if ($rep->user_id) $userIds[] = $rep->user_id;
                    }
                }
                if (!empty($userIds)) {
                    Notificacion::enviarA(array_unique($userIds), 'general', $titulo, $mensaje, ['evento_id' => $evento->id]);
                }
            } catch (\Throwable) {}
        }

        return back()->with('success', "{$insertados} estudiante(s) inscritos correctamente.");
    }

    // ── Marcar asistencia (PATCH individual o batch) ──────────────────────

    public function marcarAsistencia(Request $request, Evento $evento)
    {
        $request->validate([
            'asistencias'   => 'required|array',
            'asistencias.*' => 'boolean',
        ]);

        foreach ($request->asistencias as $inscripcionId => $asistio) {
            InscripcionEvento::where('id', $inscripcionId)
                ->where('evento_id', $evento->id)
                ->update(['asistio' => (bool) $asistio]);
        }

        return back()->with('success', 'Asistencia actualizada correctamente.');
    }

    // ── Desinscribir estudiante ───────────────────────────────────────────

    public function desinscribir(Evento $evento, Estudiante $estudiante)
    {
        InscripcionEvento::where('evento_id', $evento->id)
            ->where('estudiante_id', $estudiante->id)
            ->delete();

        return back()->with('success', 'Estudiante eliminado de la lista de inscritos.');
    }

    // ── PDF de inscritos ──────────────────────────────────────────────────

    public function inscritosExcel(Evento $evento)
    {
        $inscripciones = $evento->inscripciones()
            ->with('estudiante.matriculas.grupo.grado')
            ->get()
            ->sortBy('estudiante.apellidos');

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Inscritos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Inscritos: ' . $evento->nombre . ' — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Nombre', 'Apellidos', 'Cédula', 'Grupo', 'Asistió'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:F3')->applyFromArray($hdrStyle);

        foreach ($inscripciones->values() as $i => $ins) {
            $row = $i + 4;
            $est = $ins->estudiante;
            $matricula = $est?->matriculas->first();
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $est?->nombres ?? '—');
            $ws->setCellValue("C{$row}", $est?->apellidos ?? '—');
            $ws->setCellValue("D{$row}", $est?->cedula ?? '—');
            $ws->setCellValue("E{$row}", $matricula?->grupo?->nombre_completo ?? '—');
            $ws->setCellValue("F{$row}", $ins->asistio ? 'Sí' : 'No');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'F') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'evt_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($evento->nombre ?? 'evento');
        return response()->download($tmp, "inscritos_{$slug}_" . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function inscritosPdf(Evento $evento)
    {
        $evento->loadCount('inscripciones');

        $inscripciones = $evento->inscripciones()
            ->with('estudiante')
            ->get()
            ->sortBy('estudiante.nombre_completo');

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $sy     = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.eventos.inscritos_pdf',
            compact('evento', 'inscripciones', 'inst', 'dir', 'config')
        )->setPaper('letter', 'landscape');

        $slug = \Illuminate\Support\Str::slug($evento->nombre ?? 'evento');
        return $pdf->download("inscritos_{$slug}.pdf");
    }

    // ── Lista Excel de eventos ────────────────────────────────────────────

    public function listaExcel(Request $request)
    {
        $query = Evento::withCount('inscripciones');

        if ($request->filled('tipo'))        $query->where('tipo', $request->tipo);
        if ($request->filled('fecha_desde')) $query->where('fecha_inicio', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->where('fecha_inicio', '<=', $request->fecha_hasta);
        if ($request->filled('activo'))      $query->where('activo', $request->activo === '1');
        if ($request->filled('q'))           $query->where('nombre', 'like', '%' . $request->q . '%');

        $eventos = $query->orderByDesc('fecha_inicio')->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Eventos');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', 'Eventos Extracurriculares — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $tiposLabels = ['academico' => 'Académico', 'deportivo' => 'Deportivo', 'cultural' => 'Cultural', 'social' => 'Social', 'otro' => 'Otro'];

        foreach (['#', 'Nombre', 'Tipo', 'Fecha Inicio', 'Fecha Fin', 'Lugar', 'Inscritos', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdrStyle);

        foreach ($eventos as $i => $evt) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $evt->nombre);
            $ws->setCellValue("C{$row}", $tiposLabels[$evt->tipo] ?? $evt->tipo);
            $ws->setCellValue("D{$row}", $evt->fecha_inicio ? \Carbon\Carbon::parse($evt->fecha_inicio)->format('d/m/Y') : '—');
            $ws->setCellValue("E{$row}", $evt->fecha_fin ? \Carbon\Carbon::parse($evt->fecha_fin)->format('d/m/Y') : '—');
            $ws->setCellValue("F{$row}", $evt->lugar ?? '—');
            $ws->setCellValue("G{$row}", $evt->inscripciones_count);
            $ws->setCellValue("H{$row}", $evt->activo ? 'Activo' : 'Inactivo');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'evts_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'eventos_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $query = Evento::withCount('inscripciones');

        if ($request->filled('tipo'))        $query->where('tipo', $request->tipo);
        if ($request->filled('fecha_desde')) $query->where('fecha_inicio', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->where('fecha_inicio', '<=', $request->fecha_hasta);
        if ($request->filled('activo'))      $query->where('activo', $request->activo === '1');
        if ($request->filled('q'))           $query->where('nombre', 'like', '%' . $request->q . '%');

        $eventos = $query->orderByDesc('fecha_inicio')->get();
        $inst    = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.eventos.lista_pdf',
            compact('eventos', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('eventos_' . now()->format('Ymd') . '.pdf');
    }
}
