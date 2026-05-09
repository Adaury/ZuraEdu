<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
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
        $totalGruposAnio      = $schoolYear ? Grupo::where('school_year_id', $schoolYear->id)->count() : 0;
        $totalEstudiantesActivos = Estudiante::where('estado', 'activo')->count();

        return view('admin.matriculas.index', compact(
            'matriculas', 'schoolYear', 'grupos', 'ciclo',
            'totalGruposAnio', 'totalEstudiantesActivos'
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
        $matricula->load(['estudiante', 'grupo.grado', 'grupo.seccion', 'grupo.schoolYear', 'calificaciones']);

        return view('admin.matriculas.show', compact('matricula'));
    }

    public function destroy(Matricula $matricula)
    {
        if ($matricula->calificaciones()->count() > 0 || $matricula->asistencias()->count() > 0) {
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
            $sheet->setCellValue("E{$row}", $est->matricula ?? '');
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
