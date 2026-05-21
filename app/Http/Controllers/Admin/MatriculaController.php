<?php

namespace App\Http\Controllers\Admin;

use App\Events\DashboardActualizado;
use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatriculaController extends Controller
{
    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $ciclo      = $request->input('ciclo', ''); // 'primer_ciclo' | 'segundo_ciclo' | ''

        $query = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear?->id)
            ->orderBy('created_at', 'desc');

        // Filtrar por ciclo a través del grado del grupo
        if ($ciclo) {
            $query->whereHas('grupo.grado', fn($q) => $q->where('ciclo', $ciclo));
        }

        // Filter by grupo
        if ($request->filled('grupo_id')) {
            $query->where('grupo_id', $request->grupo_id);
        }

        // Filter by estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Search by student name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('estudiante', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('numero_matricula', 'like', "%{$search}%");
            });
        }

        $matriculas = $query->paginate(20)->withQueryString();

        // Grupos filtrados por ciclo para el dropdown
        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->when($ciclo, fn($q) => $q->whereHas('grado', fn($g) => $g->where('ciclo', $ciclo)))
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        // Conteos para el panel de configuración inicial
        $totalGruposAnio         = $schoolYear ? Grupo::where('school_year_id', $schoolYear->id)->count() : 0;
        $totalEstudiantesActivos = Estudiante::where('estado', 'activo')->count();

        // Stats rápidas para el header
        $stats = Matricula::where('school_year_id', $schoolYear?->id)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $inscPendientes = Inscripcion::where('school_year_id', $schoolYear?->id)
            ->where('estado', 'pendiente')
            ->count();

        // Estudiantes disponibles para matrícula masiva
        $enrolledIds    = Matricula::where('school_year_id', $schoolYear?->id)->pluck('estudiante_id');
        $estudiantesDisp = Estudiante::activos()
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('apellidos')
            ->get(['id', 'nombres', 'apellidos', 'numero_matricula']);

        return view('admin.matriculas.index', compact(
            'matriculas', 'schoolYear', 'grupos', 'ciclo',
            'totalGruposAnio', 'totalEstudiantesActivos',
            'stats', 'inscPendientes', 'estudiantesDisp'
        ));
    }

    public function create()
    {
        $schoolYear = SchoolYear::actual();

        // Students who are active and NOT yet enrolled in current year
        $enrolledIds = Matricula::where('school_year_id', $schoolYear?->id)
            ->pluck('estudiante_id');

        $estudiantes = Estudiante::activos()
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('apellidos')
            ->get();

        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn ($q) => $q->where('school_year_id', $schoolYear->id))
            ->activos()
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        return view('admin.matriculas.create', compact('estudiantes', 'grupos', 'schoolYear'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_year_id'  => 'required|exists:school_years,id',
            'estudiante_id'   => 'required|exists:estudiantes,id',
            'grupo_id'        => 'required|exists:grupos,id',
            'fecha_matricula' => 'required|date',
            'observaciones'   => 'nullable|string',
        ]);

        // Check student not already enrolled this year
        $alreadyEnrolled = Matricula::where('school_year_id', $data['school_year_id'])
            ->where('estudiante_id', $data['estudiante_id'])
            ->exists();

        if ($alreadyEnrolled) {
            throw ValidationException::withMessages([
                'estudiante_id' => 'Este estudiante ya está matriculado en este año escolar.',
            ]);
        }

        // Generate numero_orden
        $data['numero_orden'] = Matricula::where('grupo_id', $data['grupo_id'])->count() + 1;
        $data['estado']       = 'activa';

        $matricula = Matricula::create($data);

        try {
            DashboardActualizado::dispatch(tenant_id() ?? 0, 'nueva_matricula', [
                'grupo_id' => $matricula->grupo_id,
            ]);
        } catch (\Throwable) {}

        try {
            $matricula->load(['estudiante.representantes', 'grupo.grado', 'grupo.seccion']);
            $estudiante = $matricula->estudiante;
            $grupo      = $matricula->grupo;
            $nombre     = $grupo ? "{$grupo->grado?->nombre} {$grupo->seccion?->nombre}" : '—';
            $titulo  = '✅ Matrícula confirmada';
            $mensaje = "{$estudiante?->nombre_completo} ha sido matriculado/a en {$nombre} para el año escolar en curso.";
            if ($estudiante?->user_id) {
                Notificacion::enviar($estudiante->user_id, 'general', $titulo, $mensaje);
            }
            foreach ($estudiante?->representantes ?? [] as $rep) {
                if ($rep->user_id) {
                    Notificacion::enviar($rep->user_id, 'general', $titulo, $mensaje);
                }
            }
        } catch (\Throwable) {}

        if ($request->filled('redirect_grupo_id')) {
            return redirect()->route('admin.grupos.show', $request->input('redirect_grupo_id'))
                ->with('success', 'Estudiante matriculado correctamente.');
        }

        return redirect()->route('admin.matriculas.index')
            ->with('success', 'Matrícula registrada exitosamente.');
    }

    public function show(Matricula $matricula)
    {
        $matricula->load([
            'estudiante.representantes',
            'grupo.grado',
            'grupo.seccion',
            'grupo.schoolYear',
            'calificaciones.asignacion.asignatura',
            'calificaciones.periodo',
        ]);

        $totalAsistencias = $matricula->asistencias()->count();
        $presentes        = $matricula->asistencias()->whereIn('estado', ['presente', 'tarde'])->count();
        $pctAsistencia    = $totalAsistencias > 0 ? round($presentes / $totalAsistencias * 100, 1) : null;

        $gruposDisp = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', $matricula->grupo?->school_year_id)
            ->activos()
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        return view('admin.matriculas.show', compact(
            'matricula', 'totalAsistencias', 'presentes', 'pctAsistencia', 'gruposDisp'
        ));
    }

    public function update(Request $request, Matricula $matricula)
    {
        $data = $request->validate([
            'fecha_matricula' => 'required|date',
            'observaciones'   => 'nullable|string|max:1000',
        ]);

        $matricula->update($data);

        return back()->with('success', 'Matrícula actualizada.');
    }

    public function cambiarEstado(Request $request, Matricula $matricula)
    {
        $data = $request->validate([
            'estado' => 'required|in:activa,retirada,transferida',
            'motivo' => 'nullable|string|max:500',
        ]);

        $matricula->update([
            'estado'       => $data['estado'],
            'observaciones'=> $data['motivo']
                ? ($matricula->observaciones ? $matricula->observaciones . ' | ' : '') . $data['motivo']
                : $matricula->observaciones,
        ]);

        $labels = ['activa' => 'reactivada', 'retirada' => 'marcada como retirada', 'transferida' => 'marcada como transferida'];

        return back()->with('success', 'Matrícula ' . ($labels[$data['estado']] ?? $data['estado']) . ' correctamente.');
    }

    public function storeMasivo(Request $request)
    {
        $data = $request->validate([
            'grupo_id'          => 'required|exists:grupos,id',
            'estudiante_ids'    => 'required|array|min:1',
            'estudiante_ids.*'  => 'integer|exists:estudiantes,id',
            'fecha_matricula'   => 'required|date',
        ]);

        $schoolYear = SchoolYear::actual();
        abort_unless($schoolYear, 422, 'No hay año escolar activo.');

        $creados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($data, $schoolYear, &$creados, &$omitidos) {
            foreach ($data['estudiante_ids'] as $estId) {
                $yaExiste = Matricula::where('school_year_id', $schoolYear->id)
                    ->where('estudiante_id', $estId)
                    ->exists();

                if ($yaExiste) { $omitidos++; continue; }

                $numeroOrden = Matricula::where('grupo_id', $data['grupo_id'])->count() + $creados + 1;

                $matricula = Matricula::create([
                    'school_year_id'  => $schoolYear->id,
                    'estudiante_id'   => $estId,
                    'grupo_id'        => $data['grupo_id'],
                    'fecha_matricula' => $data['fecha_matricula'],
                    'numero_orden'    => $numeroOrden,
                    'estado'          => 'activa',
                ]);

                // Marcar inscripción como asignada si existe
                Inscripcion::where('school_year_id', $schoolYear->id)
                    ->where('estudiante_id', $estId)
                    ->where('estado', 'pendiente')
                    ->update(['estado' => 'asignada', 'grupo_id' => $data['grupo_id'], 'matricula_id' => $matricula->id]);

                $creados++;
            }

            try {
                DashboardActualizado::dispatch(tenant_id() ?? 0, 'nueva_matricula', [
                    'grupo_id' => $data['grupo_id'],
                ]);
            } catch (\Throwable) {}
        });

        $msg = "{$creados} matrícula(s) registrada(s) exitosamente.";
        if ($omitidos) $msg .= " {$omitidos} omitida(s) por ya estar matriculadas.";

        return back()->with('success', $msg);
    }

    public function resumen()
    {
        $schoolYear = SchoolYear::actual();

        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        $grupoIds = $grupos->pluck('id');

        // Conteos por estado por grupo en una sola query
        $conteosPorGrupo = Matricula::where('school_year_id', $schoolYear?->id)
            ->whereIn('grupo_id', $grupoIds)
            ->selectRaw('grupo_id, estado, count(*) as total')
            ->groupBy('grupo_id', 'estado')
            ->get()
            ->groupBy('grupo_id');

        // Totales globales
        $totales = Matricula::where('school_year_id', $schoolYear?->id)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $inscPendientes = Inscripcion::where('school_year_id', $schoolYear?->id)
            ->where('estado', 'pendiente')
            ->count();

        return view('admin.matriculas.resumen', compact(
            'schoolYear', 'grupos', 'conteosPorGrupo', 'totales', 'inscPendientes'
        ));
    }

    public function destroy(Matricula $matricula)
    {
        $matricula->loadCount(['calificaciones', 'asistencias']);
        if ($matricula->calificaciones_count > 0 || $matricula->asistencias_count > 0) {
            return back()->with('error', 'No se puede eliminar la matrícula porque tiene calificaciones o asistencias registradas. Puede cambiarla a estado "retirada" en su lugar.');
        }

        $matricula->update(['estado' => 'retirada']);

        return redirect()->route('admin.matriculas.index')
            ->with('success', 'Matrícula marcada como retirada.');
    }

    public function cambiarGrupo(Request $request, Matricula $matricula)
    {
        $data = $request->validate([
            'grupo_id'     => 'required|exists:grupos,id',
            'observaciones' => 'nullable|string',
        ]);

        $matricula->update([
            'grupo_id'      => $data['grupo_id'],
            'observaciones' => $data['observaciones'] ?? $matricula->observaciones,
        ]);

        return back()->with('success', 'Grupo de la matrícula actualizado correctamente.');
    }

    // ── Constancia de matrícula PDF ───────────────────────────────────────
    public function constancia(Matricula $matricula)
    {
        $matricula->load([
            'estudiante.representantes',
            'grupo.grado',
            'grupo.seccion',
            'schoolYear',
        ]);

        $config = \App\Models\BoletinConfig::getOrCreate($matricula->school_year_id);
        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.matriculas.constancia_pdf',
            compact('matricula', 'config', 'si', 'dir', 'cod')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($matricula->estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("constancia_{$slug}.pdf");
    }

    // ── Constancia de Estudios PDF ───────────────────────────────────────
    public function constanciaEstudios(Matricula $matricula)
    {
        $matricula->load(['estudiante.representantes', 'grupo.grado', 'grupo.seccion', 'schoolYear']);

        $sy     = SchoolYear::actual() ?? $matricula->schoolYear;
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;
        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $cod    = \App\Models\ConfigInstitucional::get('codigo_centro', '');
        $tel    = \App\Models\ConfigInstitucional::get('telefono', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.matriculas.constancia_estudios_pdf',
            compact('matricula', 'config', 'si', 'dir', 'cod', 'tel', 'sy')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($matricula->estudiante->nombre_completo ?? 'estudiante');
        return $pdf->download("constancia_estudios_{$slug}.pdf");
    }

    // ── Lista de matriculados PDF ─────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $schoolYear = \App\Models\SchoolYear::actual();
        $grupoId    = $request->grupo_id;
        $ciclo      = $request->ciclo;

        $query = Matricula::with(['estudiante', 'grupo.grado', 'grupo.seccion'])
            ->where('school_year_id', $schoolYear?->id)
            ->where('estado', 'activa')
            ->orderByHas('grupo', fn($q) => $q->orderBy('grado_id'))
            ->orderBy('numero_orden');

        if ($grupoId) $query->where('grupo_id', $grupoId);
        if ($ciclo)   $query->whereHas('grupo.grado', fn($q) => $q->where('ciclo', $ciclo));

        $matriculas = $query->get();
        $inst       = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config     = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.matriculas.lista_pdf',
            compact('matriculas', 'schoolYear', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download('matriculas_' . now()->format('Ymd') . '.pdf');
    }

    // ── Lista de matriculados Excel ────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $grupoId    = $request->grupo_id;
        $ciclo      = $request->ciclo;

        $query = Matricula::with([
            'estudiante.representantes',
            'grupo.grado',
            'grupo.seccion',
        ])
        ->where('school_year_id', $schoolYear?->id)
        ->where('estado', 'activa')
        ->orderByHas('grupo', fn($q) => $q->orderBy('grado_id'))
        ->orderBy('numero_orden');

        if ($grupoId) $query->where('grupo_id', $grupoId);
        if ($ciclo) {
            $query->whereHas('grupo.grado', fn($q) => $q->where('ciclo', $ciclo));
        }

        $matriculas = $query->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Matrículas');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'LISTA DE ESTUDIANTES MATRICULADOS — ' . ($schoolYear?->nombre ?? date('Y')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'No. Orden', 'Apellidos', 'Nombres', 'Matrícula', 'Cédula', 'Fecha Nac.', 'Grupo', 'Representante', 'Teléfono Rep.'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '2';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A2:J2')->applyFromArray($hdrStyle);

        foreach ($matriculas as $i => $m) {
            $row = $i + 3;
            $est = $m->estudiante;
            $rep = $est->representantes->first();
            $grp = $m->grupo;

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $m->numero_orden ?? '');
            $sheet->setCellValue("C{$row}", $est->apellidos ?? $est->apellido ?? '');
            $sheet->setCellValue("D{$row}", $est->nombres   ?? $est->nombre  ?? '');
            $sheet->setCellValue("E{$row}", $est->numero_matricula ?? '');
            $sheet->setCellValue("F{$row}", $est->cedula ?? '');
            $sheet->setCellValue("G{$row}", $est->fecha_nacimiento ? \Carbon\Carbon::parse($est->fecha_nacimiento)->format('d/m/Y') : '');
            $sheet->setCellValue("H{$row}", $grp ? ($grp->grado->nombre ?? '') . ' ' . ($grp->seccion->nombre ?? '') : '');
            $sheet->setCellValue("I{$row}", $rep ? trim(($rep->nombres ?? $rep->nombre ?? '') . ' ' . ($rep->apellidos ?? $rep->apellido ?? '')) : '');
            $sheet->setCellValue("J{$row}", $rep?->celular ?? $rep?->telefono ?? '');

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:J{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'J') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp      = tempnam(sys_get_temp_dir(), 'mat_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'matriculas_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
