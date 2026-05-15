<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGrupoRequest;
use App\Http\Requests\Admin\UpdateGrupoRequest;
use App\Traits\AsignaMateriasBasicas;
use Illuminate\Http\Request;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GrupoController extends Controller
{
    use AsignaMateriasBasicas;

    public function index()
    {
        $schoolYear = SchoolYear::actual();

        $grupos = Grupo::with(['grado', 'seccion', 'tutor'])
            ->withCount('matriculas')
            ->when(SchoolYear::actual(), fn ($q, $y) => $q->where('school_year_id', $y->id))
            ->orderBy('grado_id')
            ->orderBy('seccion_id')
            ->get();

        $secciones = Seccion::withCount('grupos')->orderBy('orden')->get();

        return view('admin.grupos.index', compact('grupos', 'schoolYear', 'secciones'));
    }

    public function create()
    {
        $grados     = Grado::orderBy('nivel')->get();
        $secciones  = Seccion::orderBy('orden')->get();
        $docentes   = Docente::with('user')->activos()->orderBy('apellidos')->get();
        $schoolYears = SchoolYear::orderByDesc('id')->get();

        return view('admin.grupos.create', compact('grados', 'secciones', 'docentes', 'schoolYears'));
    }

    public function store(StoreGrupoRequest $request)
    {
        $data = $request->validated();

        $data['activo'] = $request->boolean('activo');
        $data['tutor_id'] = $this->resolverTutorUserId($data['tutor_id'] ?? null);

        $grupo = Grupo::create($data);

        // Auto-asignar materias básicas al nuevo grupo
        $this->asignarMateriasBasicas($grupo->id, $grupo->school_year_id);

        return redirect()->route('admin.grupos.index')
            ->with('success', 'Grupo creado exitosamente.');
    }

    public function show(Grupo $grupo)
    {
        $grupo->load([
            'grado', 'seccion', 'schoolYear', 'tutor',
            'asignaciones' => fn ($q) => $q->with(['asignatura', 'docente'])->orderBy('asignatura_id'),
            'matriculas'   => fn ($q) => $q->with('estudiante')
                                           ->whereIn('estado', ['activa'])
                                           ->orderBy('numero_orden'),
        ]);

        $schoolYear = $grupo->schoolYear;

        // Students not yet enrolled in this school year (any group)
        $yaEnrolados = Matricula::where('school_year_id', $schoolYear?->id)->pluck('estudiante_id');
        $estudiantesDisponibles = Estudiante::orderBy('apellidos')
            ->whereNotIn('id', $yaEnrolados)
            ->get();

        // Subjects not yet assigned to this group
        $asigIdsTomadas = $grupo->asignaciones->pluck('asignatura_id');
        $asignaturasDisponibles = Asignatura::activas()
            ->whereNotIn('id', $asigIdsTomadas)
            ->orderBy('nombre')
            ->get();

        $docentes = Docente::activos()->with('user')->orderBy('apellidos')->get();

        return view('admin.grupos.show', compact(
            'grupo', 'schoolYear',
            'estudiantesDisponibles', 'asignaturasDisponibles', 'docentes'
        ));
    }

    public function edit(Grupo $grupo)
    {
        $grados      = Grado::orderBy('nivel')->get();
        $secciones   = Seccion::orderBy('orden')->get();
        $docentes    = Docente::with('user')->activos()->orderBy('apellidos')->get();
        $schoolYears = SchoolYear::orderByDesc('id')->get();

        return view('admin.grupos.edit', compact('grupo', 'grados', 'secciones', 'docentes', 'schoolYears'));
    }

    public function update(UpdateGrupoRequest $request, Grupo $grupo)
    {
        $data = $request->validated();

        $data['activo'] = $request->boolean('activo');
        $data['tutor_id'] = $this->resolverTutorUserId($data['tutor_id'] ?? null);

        $grupo->update($data);

        if (request()->input('_from_show')) {
            return redirect()->route('admin.grupos.show', $grupo)
                ->with('success', 'Grupo actualizado.');
        }

        return redirect()->route('admin.grupos.index')
            ->with('success', 'Grupo actualizado exitosamente.');
    }

    public function updateTutor(Request $request, Grupo $grupo)
    {
        $request->validate(['tutor_docente_id' => 'nullable|exists:docentes,id']);
        $tutorId = $this->resolverTutorUserId($request->integer('tutor_docente_id') ?: null);
        $grupo->update(['tutor_id' => $tutorId]);

        return redirect()->route('admin.grupos.show', $grupo)
            ->with('success', 'Maestro guía actualizado correctamente.');
    }

    public function destroy(Grupo $grupo)
    {
        $grupo->loadCount('matriculas');
        if ($grupo->matriculas_count > 0) {
            return back()->with('error', 'No se puede eliminar el grupo porque tiene matrículas asociadas.');
        }

        $grupo->delete();

        return redirect()->route('admin.grupos.index')
            ->with('success', 'Grupo eliminado exitosamente.');
    }

    /**
     * Receive a docente_id, return its linked user_id.
     * If the docente has no user account yet, create one automatically.
     */
    // ── Lista de estudiantes PDF ──────────────────────────────────────────
    public function listaPdf(Grupo $grupo)
    {
        $grupo->load([
            'grado', 'seccion', 'schoolYear', 'tutor',
            'matriculas' => fn($q) => $q->with('estudiante')
                ->whereIn('estado', ['activa'])
                ->orderBy('numero_orden'),
            'asignaciones.asignatura',
        ]);

        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $grupo->school_year_id ? \App\Models\BoletinConfig::getOrCreate($grupo->school_year_id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.grupos.lista_pdf',
            compact('grupo', 'si', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($grupo->nombre_completo ?? 'grupo');
        return $pdf->download("lista_{$slug}.pdf");
    }

    // ── Lista de estudiantes Excel ────────────────────────────────────────
    public function listaExcel(Grupo $grupo)
    {
        $grupo->load([
            'grado', 'seccion', 'schoolYear',
            'matriculas' => fn($q) => $q->with('estudiante')
                ->whereIn('estado', ['activa'])
                ->orderBy('numero_orden'),
        ]);

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Lista ' . ($grupo->nombre_completo ?? ''));

        // Título
        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', $grupo->nombre_completo ?? 'Lista de Estudiantes');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('A2:F2');
        $ws->setCellValue('A2', ($grupo->schoolYear->nombre ?? '') . ' — ' . ($grupo->grado->nombre ?? ''));
        $ws->getStyle('A2')->getFont()->setItalic(true);

        // Headers
        $headers = ['#', 'Matrícula', 'Apellidos', 'Nombre', 'Cédula', 'Representante'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '4';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($grupo->matriculas as $i => $mat) {
            $row = $i + 5;
            $est = $mat->estudiante;
            $rep = $est->representantes->first();
            $ws->setCellValue("A{$row}", $mat->numero_orden ?? ($i + 1));
            $ws->setCellValue("B{$row}", $est->matricula ?? '—');
            $ws->setCellValue("C{$row}", $est->apellidos ?? $est->apellido ?? '—');
            $ws->setCellValue("D{$row}", $est->nombres   ?? $est->nombre  ?? '—');
            $ws->setCellValue("E{$row}", $est->cedula ?? '—');
            $ws->setCellValue("F{$row}", $rep ? trim(($rep->nombres ?? '') . ' ' . ($rep->apellidos ?? '')) : '—');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'F') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($ss);
        $filename = 'lista_' . Str::slug($grupo->nombre_completo ?? 'grupo') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Notas de todo el grupo en Excel ──────────────────────────────────
    public function notasPdf(Grupo $grupo)
    {
        $grupo->load(['grado', 'seccion', 'schoolYear']);
        $sy = $grupo->schoolYear;

        $matriculas = $grupo->matriculas()
            ->with('estudiante')->whereIn('estado', ['activa'])
            ->orderBy('numero_orden')->get();

        $asignaciones = \App\Models\Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->where('activo', true)
            ->get()->sortBy(fn($a) => $a->asignatura?->nombre);

        $calAcad = \App\Models\CalificacionAcademica::with('asignacion.asignatura')
            ->where('school_year_id', $sy?->id)
            ->whereHas('matricula', fn($m) => $m->where('grupo_id', $grupo->id))
            ->get()->groupBy('matricula_id');

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.grupos.notas_pdf',
            compact('grupo', 'matriculas', 'asignaciones', 'calAcad', 'sy', 'inst', 'config')
        )->setPaper('letter', 'landscape');

        return $pdf->download('notas_' . Str::slug($grupo->nombre_completo) . '.pdf');
    }

    public function notasExcel(Grupo $grupo)
    {
        $grupo->load(['grado','seccion','schoolYear',
            'matriculas.estudiante',
            'asignaciones.asignatura',
        ]);

        $sy = $grupo->schoolYear;
        $matriculas = $grupo->matriculas()
            ->with('estudiante')->whereIn('estado',['activa'])
            ->orderBy('numero_orden')->get();

        $asignaciones = \App\Models\Asignacion::with('asignatura')
            ->where('grupo_id', $grupo->id)
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->where('activo', true)
            ->get()->sortBy(fn($a) => $a->asignatura?->nombre);

        $calAcad = \App\Models\CalificacionAcademica::with('asignacion.asignatura')
            ->where('school_year_id', $sy?->id)
            ->whereHas('matricula', fn($m) => $m->where('grupo_id', $grupo->id))
            ->get()->groupBy('matricula_id');

        $ss  = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws  = $ss->getActiveSheet();
        $ws->setTitle('Notas');

        // Título
        $lastC = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($asignaciones->count() + 4);
        $ws->mergeCells("A1:{$lastC}1");
        $ws->setCellValue('A1', 'Calificaciones — ' . $grupo->nombre_completo . ' — ' . ($sy?->nombre ?? ''));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        // Headers
        $ws->setCellValue('A3','#');
        $ws->setCellValue('B3','Matrícula');
        $ws->setCellValue('C3','Apellidos');
        $ws->setCellValue('D3','Nombre');
        $col = 5;
        foreach ($asignaciones as $asig) {
            $ws->setCellValue([$col++, 3], $asig->asignatura?->nombre ?? '—');
        }
        $ws->setCellValue([$col, 3], 'Promedio');

        // Estilo header
        $hdrRange = "A3:{$lastC}3";
        $ws->getStyle($hdrRange)->getFont()->setBold(true);
        $ws->getStyle($hdrRange)->getFill()
           ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
           ->getStartColor()->setRGB('1e3a6e');
        $ws->getStyle($hdrRange)->getFont()->getColor()->setRGB('ffffff');

        foreach ($matriculas as $i => $mat) {
            $row = $i + 4;
            $est = $mat->estudiante;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $est?->matricula ?? '');
            $ws->setCellValue("C{$row}", $est?->apellidos ?? $est?->apellido ?? '');
            $ws->setCellValue("D{$row}", $est?->nombres   ?? $est?->nombre   ?? '');

            $col = 5; $notas = [];
            $misCalifs = $calAcad[$mat->id] ?? collect();
            foreach ($asignaciones as $asig) {
                $cal  = $misCalifs->firstWhere('asignacion_id', $asig->id);
                $nota = $cal?->nota_final;
                $ws->setCellValue([$col, $row], $nota ?? '');
                if ($nota !== null) $notas[] = $nota;
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                if ($nota !== null) {
                    $ws->getStyle("{$colLetter}{$row}")->getFont()
                       ->getColor()->setRGB($nota >= 70 ? '065f46' : '991b1b');
                }
                $col++;
            }
            $prom = $notas ? round(array_sum($notas)/count($notas), 1) : null;
            $ws->setCellValue([$col, $row], $prom ?? '');
            if ($prom !== null) {
                $promLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $ws->getStyle("{$promLetter}{$row}")->getFont()->setBold(true)
                   ->getColor()->setRGB($prom >= 70 ? '065f46' : '991b1b');
            }
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:D{$row}")->getFill()
                   ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f8faff');
            }
        }

        foreach (range(1, $asignaciones->count() + 5) as $ci) {
            $ws->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci))->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $filename = 'notas_' . Str::slug($grupo->nombre_completo) . '.xlsx';

        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function resolverTutorUserId(?int $docenteId): ?int
    {
        if (!$docenteId) return null;

        $docente = Docente::find($docenteId);
        if (!$docente) return null;

        // Already linked
        if ($docente->user_id) return $docente->user_id;

        // No user yet — create one if email is available
        if ($docente->email) {
            $user = User::where('email', $docente->email)->first();
            if (!$user) {
                $password = Str::random(12);
                $user = User::create([
                    'name'     => $docente->nombre_completo,
                    'email'    => $docente->email,
                    'password' => Hash::make($password),
                    'activo'   => true,
                ]);
                $user->assignRole('Docente');
            }
            $docente->update(['user_id' => $user->id]);
            return $user->id;
        }

        return null;
    }

    // ── Asistencia del grupo Excel ───────────────────────────────────────
    public function asistenciaExcel(Grupo $grupo)
    {
        $grupo->load([
            'grado', 'seccion', 'schoolYear',
            'matriculas' => fn($q) => $q->where('estado', 'activa')
                ->orderBy('numero_orden')
                ->with([
                    'estudiante',
                    'asistencias' => fn($a) => $a->orderBy('fecha'),
                ]),
        ]);

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Asistencia');

        // Título
        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', 'ASISTENCIA — ' . ($grupo->nombre_completo ?? 'Grupo'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Encabezados
        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $headers = ['#', 'Estudiante', 'Total Clases', 'Presentes', 'Tardanzas', 'Ausentes', '% Asistencia', 'Estado'];
        foreach ($headers as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdrStyle);

        foreach ($grupo->matriculas as $idx => $mat) {
            $row  = $idx + 4;
            $est  = $mat->estudiante;
            $asis = $mat->asistencias;

            $total    = $asis->count();
            $pres     = $asis->whereIn('estado', ['presente', 'tardanza'])->count();
            $tard     = $asis->where('estado', 'tardanza')->count();
            $aus      = $asis->where('estado', 'ausente')->count();
            $pct      = $total > 0 ? round($pres / $total * 100, 1) : null;
            $estado   = $pct === null ? '—' : ($pct >= 80 ? 'Regular' : 'Riesgo');

            $ws->setCellValue("A{$row}", $idx + 1);
            $ws->setCellValue("B{$row}", trim(($est->apellidos ?? '') . ', ' . ($est->nombres ?? '')));
            $ws->setCellValue("C{$row}", $total);
            $ws->setCellValue("D{$row}", $pres);
            $ws->setCellValue("E{$row}", $tard);
            $ws->setCellValue("F{$row}", $aus);
            $ws->setCellValue("G{$row}", $pct !== null ? $pct . '%' : '—');
            $ws->setCellValue("H{$row}", $estado);

            if ($pct !== null && $pct < 80) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('fee2e2');
            } elseif ($idx % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);
        $ws->freezePane('A4');

        $writer   = new Xlsx($ss);
        $filename = 'asistencia_' . Str::slug($grupo->nombre_completo ?? 'grupo') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Asistencia del grupo PDF ──────────────────────────────────────────
    public function asistenciaPdf(Grupo $grupo)
    {
        $grupo->load([
            'grado', 'seccion', 'schoolYear',
            'matriculas' => fn($q) => $q->where('estado', 'activa')
                ->orderBy('numero_orden')
                ->with([
                    'estudiante',
                    'asistencias' => fn($a) => $a->orderBy('fecha'),
                ]),
        ]);

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.grupos.asistencia_pdf',
            compact('grupo', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('asistencia_' . Str::slug($grupo->nombre_completo ?? 'grupo') . '.pdf');
    }

    // ── Carnets estudiantiles PDF ─────────────────────────────────────────
    public function carnetsPdf(Grupo $grupo)
    {
        $grupo->load([
            'grado', 'seccion', 'schoolYear', 'tutor',
            'matriculas' => fn($q) => $q->with('estudiante')
                ->whereIn('estado', ['activa'])
                ->orderBy('numero_orden'),
        ]);

        $inst    = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $logo    = \App\Models\ConfigInstitucional::get('system_logo');
        $logoUrl = $logo ? asset('storage/' . $logo) : null;
        $config  = $grupo->school_year_id ? \App\Models\BoletinConfig::getOrCreate($grupo->school_year_id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.grupos.carnets_pdf',
            compact('grupo', 'inst', 'logoUrl', 'config')
        )->setPaper('letter', 'portrait');

        $slug = Str::slug($grupo->nombre_completo ?? 'grupo');
        return $pdf->download("carnets_{$slug}.pdf");
    }

    // ── Excel general de grupos ───────────────────────────────────────────
    public function gruposExcel()
    {
        $schoolYear = SchoolYear::actual();

        $grupos = Grupo::with(['grado', 'seccion', 'tutor'])
            ->withCount('matriculas')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')
            ->orderBy('seccion_id')
            ->get();

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Grupos');

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Grupos — ' . ($schoolYear->nombre ?? 'Todos'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Nombre', 'Grado', 'Sección', 'Tutor', 'Matrícula'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '3';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($grupos as $i => $grupo) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $grupo->nombre_completo ?? '—');
            $ws->setCellValue("C{$row}", $grupo->grado->nombre ?? '—');
            $ws->setCellValue("D{$row}", $grupo->seccion->nombre ?? '—');
            $tutor = $grupo->tutor;
            $ws->setCellValue("E{$row}", $tutor ? trim(($tutor->apellidos ?? '') . ', ' . ($tutor->nombres ?? $tutor->nombre ?? '')) : '—');
            $ws->setCellValue("F{$row}", $grupo->matriculas_count ?? 0);
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'F') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($ss);
        $filename = 'grupos_' . Str::slug($schoolYear->nombre ?? 'lista') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── PDF general de grupos ─────────────────────────────────────────────
    public function gruposPdf()
    {
        $schoolYear = SchoolYear::actual();

        $grupos = Grupo::with(['grado', 'seccion', 'tutor'])
            ->withCount('matriculas')
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')
            ->orderBy('seccion_id')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.grupos.lista_general_pdf',
            compact('grupos', 'schoolYear', 'inst')
        )->setPaper('letter', 'portrait');

        return $pdf->download('grupos_' . Str::slug($schoolYear?->nombre ?? 'lista') . '.pdf');
    }
}
