<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use App\Models\SesionTutoria;
use App\Models\Tutoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TutoriaController extends Controller
{
    // ── Asignaciones de Tutores ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        $yearId = $request->integer('year_id') ?: $schoolYear?->id;
        $years  = SchoolYear::orderByDesc('fecha_inicio')->get();

        $tutorias = Tutoria::with(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones'])
            ->when($yearId, fn($q) => $q->where('school_year_id', $yearId))
            ->orderBy('grupo_id')
            ->get();

        return view('admin.tutorias.index', compact('tutorias', 'schoolYear', 'years', 'yearId'));
    }

    public function create()
    {
        $schoolYear = SchoolYear::actual();

        $docentes = Docente::activos()->orderBy('apellidos')->get();

        // Grupos sin tutor asignado en este año escolar
        $gruposConTutoria = Tutoria::where('school_year_id', $schoolYear?->id)->pluck('grupo_id');

        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->activos()
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        return view('admin.tutorias.create', compact('docentes', 'grupos', 'schoolYear', 'gruposConTutoria'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'docente_id'    => 'required|exists:docentes,id',
            'grupo_id'      => 'required|exists:grupos,id',
            'school_year_id'=> 'required|exists:school_years,id',
            'descripcion'   => 'nullable|string|max:500',
        ]);

        // Verificar que el grupo no tenga ya un tutor en este año
        $existe = Tutoria::where('grupo_id', $request->grupo_id)
            ->where('school_year_id', $request->school_year_id)
            ->exists();

        if ($existe) {
            return back()->withInput()
                ->with('error', 'Este grupo ya tiene un tutor asignado para el año escolar seleccionado.');
        }

        $tutoria = Tutoria::create(
            $request->only(['docente_id', 'grupo_id', 'school_year_id', 'descripcion']) + ['activo' => true]
        );

        try {
            $tutoria->load(['docente.user', 'grupo.grado', 'grupo.seccion']);
            if ($tutoria->docente?->user_id) {
                $grupo = $tutoria->grupo;
                $nombreGrupo = $grupo ? "{$grupo->grado?->nombre} {$grupo->seccion?->nombre}" : '—';
                Notificacion::enviar(
                    $tutoria->docente->user_id,
                    'academica',
                    '👥 Asignación de tutoría',
                    "Has sido asignado/a como docente tutor/a del grupo {$nombreGrupo}."
                );
            }
        } catch (\Throwable) {}

        return redirect()->route('admin.tutorias.index')
            ->with('success', 'Tutor asignado correctamente al grupo.');
    }

    public function edit(Tutoria $tutoria)
    {
        $docentes = Docente::activos()->orderBy('apellidos')->get();
        return view('admin.tutorias.edit', compact('tutoria', 'docentes'));
    }

    public function update(Request $request, Tutoria $tutoria)
    {
        $request->validate([
            'docente_id'  => 'required|exists:docentes,id',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $tutoria->update([
            'docente_id'  => $request->docente_id,
            'descripcion' => $request->descripcion,
            'activo'      => $request->boolean('activo', true),
        ]);

        return redirect()->route('admin.tutorias.index')
            ->with('success', 'Tutoría actualizada correctamente.');
    }

    public function toggleActivo(Tutoria $tutoria)
    {
        $tutoria->update(['activo' => ! $tutoria->activo]);

        return back()->with('success', 'Estado de la tutoría actualizado.');
    }

    public function destroy(Tutoria $tutoria)
    {
        $grupo = $tutoria->grupo->nombre_completo ?? '';
        $tutoria->delete();

        return redirect()->route('admin.tutorias.index')
            ->with('success', "Tutoría del grupo \"{$grupo}\" eliminada.");
    }

    // ── Sesiones de Tutoría ───────────────────────────────────────────────────

    public function sesiones(Tutoria $tutoria)
    {
        $tutoria->load(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones']);

        return view('admin.tutorias.sesiones', compact('tutoria'));
    }

    public function crearSesion(Request $request, Tutoria $tutoria)
    {
        if ($request->isMethod('GET')) {
            return view('admin.tutorias.sesiones', compact('tutoria'));
        }

        $data = $request->validate([
            'fecha'                 => 'required|date',
            'tema'                  => 'required|string|max:255',
            'descripcion'           => 'nullable|string',
            'estudiantes_atendidos' => 'nullable|string',
            'acuerdos'              => 'nullable|string',
            'proxima_sesion'        => 'nullable|date|after:fecha',
        ]);

        $tutoria->sesiones()->create($data);

        return redirect()->route('admin.tutorias.sesiones', $tutoria)
            ->with('success', 'Sesión registrada exitosamente.');
    }

    public function editarSesion(Request $request, Tutoria $tutoria, SesionTutoria $sesion)
    {
        // Verificar que la sesión pertenece a esta tutoría
        abort_unless($sesion->tutoria_id === $tutoria->id, 404);

        if ($request->isMethod('GET')) {
            $tutoria->load(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones']);
            return view('admin.tutorias.sesiones', compact('tutoria', 'sesion'));
        }

        $data = $request->validate([
            'fecha'                 => 'required|date',
            'tema'                  => 'required|string|max:255',
            'descripcion'           => 'nullable|string',
            'estudiantes_atendidos' => 'nullable|string',
            'acuerdos'              => 'nullable|string',
            'proxima_sesion'        => 'nullable|date',
        ]);

        $sesion->update($data);

        return redirect()->route('admin.tutorias.sesiones', $tutoria)
            ->with('success', 'Sesión actualizada correctamente.');
    }

    public function eliminarSesion(Tutoria $tutoria, SesionTutoria $sesion)
    {
        abort_unless($sesion->tutoria_id === $tutoria->id, 404);

        $sesion->delete();

        return redirect()->route('admin.tutorias.sesiones', $tutoria)
            ->with('success', 'Sesión eliminada.');
    }

    // ── Lista Excel ───────────────────────────────────────────────────────────

    public function listaExcel(Request $request)
    {
        $yearId   = $request->integer('year_id') ?: SchoolYear::actual()?->id;

        $tutorias = Tutoria::with(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones'])
            ->when($yearId, fn($q) => $q->where('school_year_id', $yearId))
            ->orderBy('grupo_id')
            ->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Tutorías');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '4338ca']],
        ];

        $ws->mergeCells('A1:F1');
        $ws->setCellValue('A1', 'Lista de Tutorías — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Grupo', 'Docente Tutor', 'Sesiones', 'Año Escolar', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:F3')->applyFromArray($hdrStyle);

        foreach ($tutorias->values() as $i => $t) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $t->grupo?->nombre_completo ?? '—');
            $ws->setCellValue("C{$row}", $t->docente?->nombre_completo ?? '—');
            $ws->setCellValue("D{$row}", $t->sesiones->count());
            $ws->setCellValue("E{$row}", $t->schoolYear?->nombre ?? '—');
            $ws->setCellValue("F{$row}", $t->activo ? 'Activo' : 'Inactivo');
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('eef2ff');
            }
        }

        foreach (range('A', 'F') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'tutorias_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'tutorias_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────────

    public function listaPdf(Request $request)
    {
        $yearId = $request->integer('year_id') ?: SchoolYear::actual()?->id;

        $tutorias = Tutoria::with(['docente', 'grupo.grado', 'grupo.seccion', 'sesiones'])
            ->when($yearId, fn($q) => $q->where('school_year_id', $yearId))
            ->orderBy('grupo_id')
            ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView('admin.tutorias.lista_pdf', compact('tutorias', 'inst'))
            ->setPaper('letter', 'landscape');

        return $pdf->download('tutorias_' . now()->format('Ymd') . '.pdf');
    }

    // ── Informe PDF ───────────────────────────────────────────────────────────

    public function informePdf(Tutoria $tutoria)
    {
        $tutoria->load(['docente', 'grupo.grado', 'grupo.seccion', 'grupo.estudiantes', 'schoolYear', 'sesionesAsc']);

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = Pdf::loadView('admin.tutorias.informe_pdf', compact('tutoria', 'inst'))
            ->setPaper('letter', 'portrait');

        $nombreGrupo = $tutoria->grupo->nombre_completo ?? 'grupo';
        $filename    = 'informe_tutoria_' . str_replace(' ', '_', strtolower($nombreGrupo)) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }
}
