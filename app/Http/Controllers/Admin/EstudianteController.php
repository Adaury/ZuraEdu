<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\AsignaMateriasBasicas;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use Illuminate\Database\QueryException;
use App\Http\Requests\Admin\StoreEstudianteRequest;
use App\Http\Requests\Admin\UpdateEstudianteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EstudianteController extends Controller
{
    use AsignaMateriasBasicas;

    // ── Index ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $buscar  = trim($request->input('buscar', ''));
        $campo   = $request->input('campo', 'todo');   // todo|nombre|apellido|cedula|codigo
        $letra   = strtoupper(trim($request->input('letra', '')));  // A-Z primera letra apellido
        $gradoId = $request->input('grado');
        $ciclo   = $request->input('ciclo');
        $area    = $request->input('area');

        $grados = Grado::orderBy('nivel')->get();

        $estudiantes = Estudiante::query()
            // ── Búsqueda por texto ────────────────────────────────────────
            ->when($buscar !== '', function ($q) use ($buscar, $campo) {
                $term = "%{$buscar}%";
                $q->where(function ($q) use ($buscar, $campo, $term) {
                    match ($campo) {
                        'nombre'   => $q->where('nombres', 'like', $term),
                        'apellido' => $q->where('apellidos', 'like', $term),
                        'cedula'   => $q->where('cedula', 'like', $term),
                        'codigo'   => $q->where('numero_matricula', 'like', $term),
                        default    => $q->where('nombres',           'like', $term)
                                        ->orWhere('apellidos',        'like', $term)
                                        ->orWhere('cedula',           'like', $term)
                                        ->orWhere('numero_matricula', 'like', $term),
                    };
                });
            })
            // ── Primera letra del apellido (A-Z, incluye tildes) ─────────
            ->when($letra !== '', function ($q) use ($letra) {
                $variantes = [
                    'A' => ['A','Á','À'], 'E' => ['E','É','È'],
                    'I' => ['I','Í','Ì'], 'O' => ['O','Ó','Ò'],
                    'U' => ['U','Ú','Ù'],
                ];
                $letras = $variantes[$letra] ?? [$letra];
                $q->where(function ($q) use ($letras) {
                    foreach ($letras as $l) {
                        $q->orWhere('apellidos', 'like', "{$l}%");
                    }
                });
            })
            // ── Filtro por grado ──────────────────────────────────────────
            ->when($gradoId, fn($q) =>
                $q->whereHas('matriculas', fn($q) => $q->activas()
                    ->whereHas('grupo', fn($q) => $q->where('grado_id', $gradoId)))
            )
            // ── Filtro por ciclo ──────────────────────────────────────────
            ->when($ciclo == 1, fn($q) =>
                $q->whereHas('matriculas', fn($q) => $q->activas()
                    ->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [1, 3])))
            )
            ->when($ciclo == 2, fn($q) =>
                $q->whereHas('matriculas', fn($q) => $q->activas()
                    ->whereHas('grupo.grado', fn($g) => $g->whereBetween('nivel', [4, 6])))
            )
            ->with(['matriculas' => fn($q) => $q->where('estado','activa')->with(['grupo.grado','grupo.seccion'])])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(20)
            ->withQueryString();

        $contexto = null;
        if ($ciclo == 1) $contexto = 'Primer Ciclo (1ro–3ro)';
        elseif ($ciclo == 2 && $area === 'academica') $contexto = 'Segundo Ciclo — Área Académica';
        elseif ($ciclo == 2 && $area === 'tecnica')   $contexto = 'Segundo Ciclo — Área Técnica';

        $hayFiltros = $buscar !== '' || $letra !== '' || $gradoId;

        return view('admin.estudiantes.index', compact(
            'estudiantes', 'buscar', 'campo', 'letra',
            'grados', 'gradoId', 'ciclo', 'area', 'contexto', 'hayFiltros'
        ));
    }

    // ── Create ─────────────────────────────────────────────────────────────
    public function create()
    {
        return view('admin.estudiantes.create');
    }

    // ── Wizard ─────────────────────────────────────────────────────────────
    public function wizard()
    {
        $schoolYear = SchoolYear::activo()->first();

        $grupos = Grupo::with(['grado', 'seccion'])
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->join('secciones', 'secciones.id', '=', 'grupos.seccion_id')
            ->when($schoolYear, fn($q) => $q->where('grupos.school_year_id', $schoolYear->id))
            ->orderBy('grados.nivel')
            ->orderBy('secciones.nombre')
            ->select('grupos.*')
            ->get();

        return view('admin.estudiantes.wizard', compact('grupos', 'schoolYear'));
    }

    // ── Store ──────────────────────────────────────────────────────────────
    public function store(StoreEstudianteRequest $request)
    {
        // Auto-generate numero_matricula if empty
        if (empty($request->input('numero_matricula'))) {
            $year  = date('Y');
            $count = Estudiante::whereYear('created_at', $year)->count() + 1;
            $numero_matricula = $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            $request->merge(['numero_matricula' => $numero_matricula]);
        }

        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->procesarFoto($request->file('foto'), 'fotos/estudiantes');
        }

        $estudiante = Estudiante::create($data);

        // Matricular automáticamente si viene grupo_id desde el wizard
        $grupoId = $request->input('grupo_id');
        if ($grupoId) {
            $schoolYear = SchoolYear::activo()->first();
            if ($schoolYear) {
                Matricula::create([
                    'school_year_id'  => $schoolYear->id,
                    'estudiante_id'   => $estudiante->id,
                    'grupo_id'        => $grupoId,
                    'fecha_matricula' => now()->toDateString(),
                    'estado'          => 'activa',
                ]);
            }
        }

        return redirect()->route('admin.estudiantes.index')
                         ->with('success', 'Estudiante registrado correctamente.');
    }

    // ── Show ───────────────────────────────────────────────────────────────
    public function show(Estudiante $estudiante)
    {
        $estudiante->load([
            'matriculas.grupo.grado',
            'matriculas.grupo.seccion',
            'matriculas.schoolYear',
        ]);

        // Cargar calificaciones del año escolar actual
        $schoolYear = SchoolYear::actual();
        $matriculaActual = $estudiante->matriculas
            ->where('estado', 'activa')
            ->when($schoolYear, fn($c) => $c->where('school_year_id', $schoolYear->id))
            ->first();

        $periodos     = collect();
        $calificaciones = collect(); // keyed by [asignacion_id][periodo_id]
        $asignaciones = collect();

        if ($matriculaActual) {
            $periodos = \App\Models\Periodo::where('school_year_id', $matriculaActual->school_year_id)
                ->orderBy('numero')
                ->get();

            $asignaciones = \App\Models\Asignacion::with(['asignatura', 'docente'])
                ->where('grupo_id', $matriculaActual->grupo_id)
                ->where('activo', true)
                ->get()
                ->sortBy('asignatura.nombre');

            $cals = \App\Models\Calificacion::where('matricula_id', $matriculaActual->id)
                ->get();

            foreach ($cals as $cal) {
                $calificaciones[$cal->asignacion_id][$cal->periodo_id] = $cal;
            }
        }

        return view('admin.estudiantes.show', compact(
            'estudiante', 'schoolYear', 'matriculaActual',
            'periodos', 'asignaciones', 'calificaciones'
        ));
    }

    // ── Edit ───────────────────────────────────────────────────────────────
    public function edit(Estudiante $estudiante)
    {
        return view('admin.estudiantes.edit', compact('estudiante'));
    }

    // ── Update ─────────────────────────────────────────────────────────────
    public function update(UpdateEstudianteRequest $request, Estudiante $estudiante)
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            if ($estudiante->foto) {
                Storage::disk('public')->delete($estudiante->foto);
            }
            $data['foto'] = $this->procesarFoto($request->file('foto'), 'fotos/estudiantes');
        }

        $estudiante->update($data);

        return redirect()->route('admin.estudiantes.index')
                         ->with('success', 'Estudiante actualizado correctamente.');
    }

    // ── Destroy ────────────────────────────────────────────────────────────
    public function destroy(Estudiante $estudiante)
    {
        if ($estudiante->foto) {
            Storage::disk('public')->delete($estudiante->foto);
        }

        $estudiante->delete();

        return redirect()->route('admin.estudiantes.index')
                         ->with('success', 'Estudiante eliminado correctamente.');
    }

    // ── Import: show form ──────────────────────────────────────────────────
    public function import(Request $request)
    {
        $ciclo = $request->input('ciclo');
        $area  = $request->input('area');

        $schoolYear = SchoolYear::activo()->first();

        $grupos = Grupo::with(['grado', 'seccion'])
            ->join('grados', 'grados.id', '=', 'grupos.grado_id')
            ->join('secciones', 'secciones.id', '=', 'grupos.seccion_id')
            ->when($schoolYear, fn($q) => $q->where('grupos.school_year_id', $schoolYear->id))
            ->orderBy('grados.nivel')
            ->orderBy('secciones.nombre')
            ->select('grupos.*')
            ->get();

        $contexto = null;
        if ($ciclo == 1) $contexto = 'Primer Ciclo (1ro–3ro)';
        elseif ($ciclo == 2 && $area === 'academica') $contexto = 'Segundo Ciclo — Área Académica';
        elseif ($ciclo == 2 && $area === 'tecnica')   $contexto = 'Segundo Ciclo — Área Técnica';

        return view('admin.estudiantes.import', compact('grupos', 'schoolYear', 'ciclo', 'area', 'contexto'));
    }

    // ── Download import template ───────────────────────────────────────────
    public function downloadTemplate(Request $request)
    {
        $format = $request->input('format', 'csv'); // csv | xlsx

        // Columnas básicas (obligatorias)
        $required = ['No.', 'Sección', 'Apellidos', 'Nombres'];
        // Columnas opcionales
        $optional = [
            'Cédula', 'Sexo', 'Nacionalidad', 'Teléfono', 'Email',
            'Dirección', 'Sector', 'Municipio', 'Provincia',
            'Nombre del Tutor', 'Tel. Tutor', 'Estado',
        ];
        $headers_row = array_merge($required, $optional);

        // Filas de ejemplo
        $sample1 = ['1', 'A', 'Álvarez García',  'Arisleidy Paola',
                    '', 'F', 'Dominicana', '', '', '', '', '', '', '', '', ''];
        $sample2 = ['2', 'A', 'Bautista Mata',   'Nashley Shanel',
                    '', 'F', 'Dominicana', '', '', '', '', '', '', '', '', ''];
        $sample3 = ['3', 'B', 'López Rodríguez', 'Carlos Manuel',
                    '001-1234567-8', 'M', 'Dominicana', '809-555-0001', '', 'Calle 5', '', 'Santiago', 'Santiago', 'Madre María', '809-555-0002', 'activo'];

        if ($format === 'xlsx' && class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Plantilla Estudiantes');

            $sheet->fromArray([$headers_row], null, 'A1');

            // Cabecera azul oscuro para columnas obligatorias (A-E)
            $styleReq = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            // Cabecera gris para columnas opcionales (F-Q)
            $styleOpt = [
                'font'      => ['bold' => true, 'color' => ['rgb' => '444444']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers_row));
            $lastOptCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($required));
            $nextOptCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($required) + 1);

            $sheet->getStyle('A1:' . $lastOptCol . '1')->applyFromArray($styleReq);
            $sheet->getStyle($nextOptCol . '1:' . $lastCol . '1')->applyFromArray($styleOpt);

            // Filas de ejemplo
            $sheet->fromArray([$sample1], null, 'A2');
            $sheet->fromArray([$sample2], null, 'A3');
            $sheet->fromArray([$sample3], null, 'A4');

            // Auto-width
            foreach (range(1, count($headers_row)) as $i) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Leyenda
            $sheet->setCellValue('A6', 'NOTAS:');
            $sheet->setCellValue('B6', 'Sexo = M o F  |  Estado = activo / inactivo / egresado / transferido  |  Solo Apellidos y Nombres son obligatorios. Las columnas grises son opcionales.');
            $sheet->getStyle('A6:B6')->getFont()->setItalic(true)->setSize(9);
            $sheet->mergeCells('B6:' . $lastCol . '6');
            $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(9);

            $writer = new XlsxWriter($spreadsheet);
            $tmpFile = tempnam(sys_get_temp_dir(), 'plantilla_') . '.xlsx';
            $writer->save($tmpFile);

            return response()->download($tmpFile, 'plantilla_estudiantes.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // Default: CSV (tab-separated para coincidir con formato de lista escolar)
        $filename = 'plantilla_estudiantes.csv';
        $callback = function () use ($headers_row, $sample1, $sample2, $sample3) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel UTF-8
            fputcsv($out, $headers_row, ',');
            fputcsv($out, $sample1, ',');
            fputcsv($out, $sample2, ',');
            fputcsv($out, $sample3, ',');
            // Nota al pie
            fputcsv($out, ['# NOTAS: Solo Apellidos y Nombres son obligatorios. Sexo=M/F | Estado=activo/inactivo/egresado/transferido | Las demás columnas son opcionales'], ',');
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Import: previsualizar archivo (multi-hoja) ─────────────────────────
    public function importPreview(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls,ods|max:10240',
        ]);

        $ciclo    = $request->input('ciclo');
        $area     = $request->input('area');
        $file     = $request->file('archivo');
        $extension = strtolower($file->getClientOriginalExtension());
        $origName  = $file->getClientOriginalName();
        $path      = $file->getRealPath();

        // Guardar archivo temporal
        $tempName = 'import_' . uniqid() . '.' . $extension;
        $tempPath = 'import_temp/' . $tempName;
        Storage::disk('local')->put($tempPath, file_get_contents($path));

        $hojas = []; // [['nombre'=>..., 'filas'=>[...]], ...]
        $isExcel = in_array($extension, ['xlsx', 'xls', 'ods']);

        if ($isExcel && class_exists(IOFactory::class)) {
            try {
                $spreadsheet = IOFactory::load(storage_path('app/' . $tempPath));
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $datos = $sheet->toArray(null, true, false, false);
                    $datos = array_values(array_filter($datos, fn($r) =>
                        count(array_filter($r, fn($v) => trim((string)$v) !== '')) > 0
                    ));
                    if (count($datos) < 2) continue;

                    $cabecera = array_map(fn($c) => $this->normalizarColumna((string)$c), $datos[0]);
                    $filas = [];
                    foreach (array_slice($datos, 1) as $fila) {
                        if (count(array_filter($fila, fn($v) => trim((string)$v) !== '')) === 0) continue;
                        $d = array_combine($cabecera, array_pad(array_map('strval', $fila), count($cabecera), ''));
                        $nombres   = trim($d['nombres']   ?? '');
                        $apellidos = trim($d['apellidos'] ?? '');
                        if (!$nombres && !$apellidos) continue;
                        $filas[] = $d;
                    }
                    if (!empty($filas)) {
                        $hojas[] = ['nombre' => $sheet->getTitle(), 'filas' => $filas];
                    }
                }
            } catch (\Exception $e) {
                Storage::disk('local')->delete($tempPath);
                return back()->withErrors(['archivo' => 'No se pudo leer el archivo Excel: ' . $e->getMessage()]);
            }
        } else {
            // CSV / TXT / TSV — una sola hoja; agrupar por columna Sección si existe
            $rawContent = file_get_contents($path);
            $encoding   = mb_detect_encoding($rawContent, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
            }
            $rawContent = ltrim($rawContent, "\xEF\xBB\xBF");

            $tmpPath = tempnam(sys_get_temp_dir(), 'sge_prev_');
            file_put_contents($tmpPath, $rawContent);
            $handle = fopen($tmpPath, 'r');

            $firstLine = rtrim((string) fgets($handle), "\r\n");
            rewind($handle);
            $candidates = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
            foreach (array_keys($candidates) as $d) {
                $candidates[$d] = count(str_getcsv($firstLine, $d));
            }
            arsort($candidates);
            $delimiter = (string) array_key_first($candidates);
            if ($candidates[$delimiter] <= 1) $delimiter = ',';

            $headerRow = fgetcsv($handle, 0, $delimiter);
            if ($headerRow) {
                $cabecera = array_map(fn($c) => $this->normalizarColumna($c), $headerRow);
                $todasFilas = [];
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
                    $d = array_combine($cabecera, array_pad(array_map('strval', $row), count($cabecera), ''));
                    $nombres   = trim($d['nombres']   ?? '');
                    $apellidos = trim($d['apellidos'] ?? '');
                    if (!$nombres && !$apellidos) continue;
                    $todasFilas[] = $d;
                }

                $tieneSec = in_array('_seccion', $cabecera);
                if ($tieneSec) {
                    $porSeccion = [];
                    $ordenSec   = [];
                    foreach ($todasFilas as $fila) {
                        $sec = strtoupper(trim($fila['_seccion'] ?? '')) ?: 'Sin sección';
                        if (!isset($porSeccion[$sec])) { $porSeccion[$sec] = []; $ordenSec[] = $sec; }
                        $porSeccion[$sec][] = $fila;
                    }
                    foreach ($ordenSec as $sec) {
                        $hojas[] = ['nombre' => $sec, 'filas' => $porSeccion[$sec]];
                    }
                } else {
                    if (!empty($todasFilas)) {
                        $hojas[] = ['nombre' => $origName, 'filas' => $todasFilas];
                    }
                }
            }
            fclose($handle);
            @unlink($tmpPath);
        }

        if (empty($hojas)) {
            Storage::disk('local')->delete($tempPath);
            return back()->withErrors(['archivo' => 'El archivo no contiene datos válidos con Nombres y Apellidos.']);
        }

        $schoolYear = SchoolYear::activo()->first();

        // Cargar grupos existentes del año escolar actual
        $gruposExistentes = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $grados   = Grado::orderBy('nivel')->get();
        $secciones = Seccion::orderBy('orden')->get();

        // Analizar cada hoja: detectar grado+sección y si el grupo existe o debe crearse
        $analisis = [];
        foreach ($hojas as $idx => $hoja) {
            $analisis[$idx] = $this->analizarHoja($hoja['nombre'], $gruposExistentes, $schoolYear);
        }

        return view('admin.estudiantes.import-preview', compact(
            'hojas', 'schoolYear', 'tempPath', 'extension',
            'ciclo', 'area', 'origName', 'analisis', 'gruposExistentes',
            'grados', 'secciones'
        ));
    }

    // ── Import: confirmar e importar desde archivo temporal ────────────────
    public function importConfirm(Request $request)
    {
        $request->validate(['temp_path' => 'required|string']);

        $tempPath   = $request->input('temp_path');
        $extension  = $request->input('extension', 'xlsx');
        $ciclo      = $request->input('ciclo');
        $area       = $request->input('area');
        $schoolYear = SchoolYear::activo()->first();

        if (!Str::startsWith($tempPath, 'import_temp/') || !Storage::disk('local')->exists($tempPath)) {
            return back()->withErrors(['archivo' => 'Archivo temporal no encontrado. Por favor, sube el archivo nuevamente.']);
        }

        $fullPath  = storage_path('app/' . $tempPath);
        $isExcel   = in_array($extension, ['xlsx', 'xls', 'ods']);
        $hojasData = [];

        if ($isExcel && class_exists(IOFactory::class)) {
            try {
                $spreadsheet = IOFactory::load($fullPath);
                $idx = 0;
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $datos = $sheet->toArray(null, true, false, false);
                    $datos = array_values(array_filter($datos, fn($r) =>
                        count(array_filter($r, fn($v) => trim((string)$v) !== '')) > 0
                    ));
                    if (count($datos) < 2) continue;

                    $cabecera = array_map(fn($c) => $this->normalizarColumna((string)$c), $datos[0]);
                    $filas = [];
                    foreach (array_slice($datos, 1) as $fila) {
                        if (count(array_filter($fila, fn($v) => trim((string)$v) !== '')) === 0) continue;
                        $d = array_combine($cabecera, array_pad(array_map('strval', $fila), count($cabecera), ''));
                        if (!trim($d['nombres'] ?? '') && !trim($d['apellidos'] ?? '')) continue;
                        $filas[] = $d;
                    }
                    if (!empty($filas)) {
                        $hojasData[] = ['nombre' => $sheet->getTitle(), 'filas' => $filas];
                    }
                }
            } catch (\Exception $e) {
                Storage::disk('local')->delete($tempPath);
                return back()->withErrors(['archivo' => 'Error al leer el archivo: ' . $e->getMessage()]);
            }
        } else {
            $rawContent = Storage::disk('local')->get($tempPath);
            $encoding   = mb_detect_encoding($rawContent, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
            }
            $rawContent = ltrim($rawContent, "\xEF\xBB\xBF");
            $tmp2 = tempnam(sys_get_temp_dir(), 'sge_conf_');
            file_put_contents($tmp2, $rawContent);
            $h = fopen($tmp2, 'r');
            $fl = rtrim((string) fgets($h), "\r\n"); rewind($h);
            $cands = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
            foreach (array_keys($cands) as $d) $cands[$d] = count(str_getcsv($fl, $d));
            arsort($cands); $delim = (string) array_key_first($cands);
            if ($cands[$delim] <= 1) $delim = ',';
            $headerRow = fgetcsv($h, 0, $delim);
            if ($headerRow) {
                $cabecera = array_map(fn($c) => $this->normalizarColumna($c), $headerRow);
                $todasFilas = [];
                while (($row = fgetcsv($h, 0, $delim)) !== false) {
                    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
                    $d = array_combine($cabecera, array_pad(array_map('strval', $row), count($cabecera), ''));
                    if (!trim($d['nombres'] ?? '') && !trim($d['apellidos'] ?? '')) continue;
                    $todasFilas[] = $d;
                }
                if (in_array('_seccion', $cabecera)) {
                    $por = []; $ord = [];
                    foreach ($todasFilas as $f) {
                        $s = strtoupper(trim($f['_seccion'] ?? '')) ?: 'Sin sección';
                        if (!isset($por[$s])) { $por[$s] = []; $ord[] = $s; }
                        $por[$s][] = $f;
                    }
                    foreach ($ord as $s) $hojasData[] = ['nombre' => $s, 'filas' => $por[$s]];
                } else {
                    if (!empty($todasFilas)) $hojasData[] = ['nombre' => 'Archivo', 'filas' => $todasFilas];
                }
            }
            fclose($h); @unlink($tmp2);
        }

        // ── Grupos existentes para cruce ────────────────────────────────────
        $gruposExistentes = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->get();

        $importados      = 0;
        $omitidos        = 0;
        $errores         = [];
        $gruposCreados   = [];
        $year            = date('Y');
        $gruposManuales  = $request->input('grupo_manual', []);
        $nuevosGrados    = $request->input('nuevo_grado', []);
        $nuevasSecciones = $request->input('nuevo_seccion', []);
        $nuevasSecNombres = $request->input('nuevo_seccion_nombre', []);

        foreach ($hojasData as $sheetIdx => $sheetInfo) {
            // ── Resolver o crear el grupo para esta hoja ─────────────────────
            $grupoId = null;

            if ($schoolYear) {
                $info = $this->analizarHoja($sheetInfo['nombre'], $gruposExistentes, $schoolYear);

                if ($info['grupo']) {
                    // Grupo ya existe (detección automática)
                    $grupoId = $info['grupo']->id;
                } elseif ($info['grado'] && $info['seccion']) {
                    // Crear el grupo automáticamente
                    $nuevoGrupo = Grupo::create([
                        'school_year_id' => $schoolYear->id,
                        'grado_id'       => $info['grado']->id,
                        'seccion_id'     => $info['seccion']->id,
                        'activo'         => true,
                    ]);
                    $grupoId = $nuevoGrupo->id;
                    $gruposCreados[] = $info['label'];
                    // Auto-asignar materias básicas al grupo recién creado
                    $this->asignarMateriasBasicas($nuevoGrupo->id, $schoolYear->id);
                    // Agregar a la colección para que las siguientes hojas lo encuentren
                    $nuevoGrupo->setRelation('grado',   $info['grado']);
                    $nuevoGrupo->setRelation('seccion', $info['seccion']);
                    $gruposExistentes->push($nuevoGrupo);
                } elseif (!empty($gruposManuales[$sheetIdx]) && $gruposManuales[$sheetIdx] !== '__nuevo__') {
                    // Grupo existente seleccionado manualmente
                    $grupoId = (int) $gruposManuales[$sheetIdx];
                } elseif (
                    ($gruposManuales[$sheetIdx] ?? '') === '__nuevo__' &&
                    !empty($nuevosGrados[$sheetIdx])
                ) {
                    // Crear nuevo grupo con grado + sección especificados
                    $gId = (int) $nuevosGrados[$sheetIdx];
                    $secVal = $nuevasSecciones[$sheetIdx] ?? '';

                    if ($secVal === '__nueva__') {
                        $letra = strtoupper(trim($nuevasSecNombres[$sheetIdx] ?? 'A')) ?: 'A';
                        $seccion = Seccion::firstOrCreate(
                            ['nombre' => $letra],
                            ['orden'  => ord($letra) - ord('A') + 1]
                        );
                        $sId = $seccion->id;
                    } else {
                        $sId = (int) $secVal;
                    }

                    if ($gId && $sId) {
                        $nuevoGrupo = Grupo::firstOrCreate(
                            ['school_year_id' => $schoolYear->id, 'grado_id' => $gId, 'seccion_id' => $sId],
                            ['activo' => true]
                        );
                        if ($nuevoGrupo->wasRecentlyCreated) {
                            $nuevoGrupo->load(['grado', 'seccion']);
                            $gruposCreados[] = ($nuevoGrupo->grado->nombre ?? 'Grado') . ' — Sección ' . ($nuevoGrupo->seccion->nombre ?? '?');
                            $this->asignarMateriasBasicas($nuevoGrupo->id, $schoolYear->id);
                            $gruposExistentes->push($nuevoGrupo);
                        }
                        $grupoId = $nuevoGrupo->id;
                    }
                }
            }

            // ── Importar estudiantes de esta hoja ────────────────────────────
            $numFila = 0;
            foreach ($sheetInfo['filas'] as $rowData) {
                $numFila++;
                $nombres   = trim($rowData['nombres']   ?? '');
                $apellidos = trim($rowData['apellidos'] ?? '');

                if (!$nombres || !$apellidos) {
                    $errores[] = "Hoja \"{$sheetInfo['nombre']}\" fila {$numFila}: nombres y apellidos son obligatorios.";
                    $omitidos++; continue;
                }

                $cedula = trim($rowData['cedula'] ?? '') ?: null;
                if ($cedula && Estudiante::where('cedula', $cedula)->exists()) {
                    $errores[] = "Hoja \"{$sheetInfo['nombre']}\" fila {$numFila}: cédula {$cedula} ya registrada — omitida.";
                    $omitidos++; continue;
                }

                $numMatricula = trim($rowData['numero_matricula'] ?? '') ?: null;
                if (!$numMatricula) {
                    do {
                        $count = Estudiante::whereYear('created_at', $year)->count() + $importados + 1;
                        $numMatricula = $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
                    } while (Estudiante::where('numero_matricula', $numMatricula)->exists());
                } elseif (Estudiante::where('numero_matricula', $numMatricula)->exists()) {
                    $errores[] = "Hoja \"{$sheetInfo['nombre']}\" fila {$numFila}: matrícula {$numMatricula} ya existe — omitida.";
                    $omitidos++; continue;
                }

                $sexo   = strtoupper(trim($rowData['sexo']   ?? ''));
                $estado = trim($rowData['estado'] ?? '');
                $fecha  = ($r = trim($rowData['fecha_nacimiento'] ?? '')) ? $this->parsearFecha($r) : null;

                try {
                    $estudiante = Estudiante::create([
                        'numero_matricula' => $numMatricula,
                        'cedula'           => $cedula,
                        'nombres'          => $nombres,
                        'apellidos'        => $apellidos,
                        'fecha_nacimiento' => $fecha,
                        'sexo'             => in_array($sexo, ['M','F']) ? $sexo : 'M',
                        'nacionalidad'     => trim($rowData['nacionalidad'] ?? '') ?: 'Dominicana',
                        'telefono'         => trim($rowData['telefono']     ?? '') ?: null,
                        'email'            => trim($rowData['email']        ?? '') ?: null,
                        'direccion'        => trim($rowData['direccion']    ?? '') ?: null,
                        'sector'           => trim($rowData['sector']       ?? '') ?: null,
                        'municipio'        => trim($rowData['municipio']    ?? '') ?: null,
                        'provincia'        => trim($rowData['provincia']    ?? '') ?: null,
                        'tutor_nombre'     => trim($rowData['tutor_nombre']   ?? '') ?: null,
                        'tutor_telefono'   => trim($rowData['tutor_telefono'] ?? '') ?: null,
                        'estado'           => in_array($estado, ['activo','inactivo','egresado','transferido']) ? $estado : 'activo',
                    ]);

                    if ($grupoId && $schoolYear) {
                        $yaMatriculado = Matricula::where('estudiante_id', $estudiante->id)
                            ->where('school_year_id', $schoolYear->id)->exists();
                        if (!$yaMatriculado) {
                            Matricula::create([
                                'school_year_id'  => $schoolYear->id,
                                'estudiante_id'   => $estudiante->id,
                                'grupo_id'        => $grupoId,
                                'fecha_matricula' => now()->toDateString(),
                                'estado'          => 'activa',
                            ]);
                        }
                    }
                    $importados++;
                } catch (QueryException $e) {
                    $errores[] = "Hoja \"{$sheetInfo['nombre']}\" fila {$numFila}: " .
                        (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062
                            ? "{$nombres} {$apellidos} — duplicado, omitido."
                            : "error al guardar.");
                    $omitidos++;
                }
            }
        }

        Storage::disk('local')->delete($tempPath);

        $msg = "Importación completada: {$importados} estudiante(s) registrado(s)";
        if (count($gruposCreados) > 0) {
            $msg .= '. Grupos creados automáticamente: ' . implode(', ', $gruposCreados);
        }
        $msg .= '.';
        if ($omitidos) $msg .= " {$omitidos} fila(s) omitida(s).";

        if ($errores) {
            return redirect()
                ->route('admin.estudiantes.import', array_filter(['ciclo' => $ciclo, 'area' => $area]))
                ->with('success', $msg)
                ->with('errores_import', $errores);
        }
        return redirect()->route('admin.estudiantes.index')->with('success', $msg);
    }

    // ── Import: process file (CSV, TXT, TSV, XLSX, XLS) ───────────────────
    public function importStore(Request $request)
    {
        $request->validate([
            'archivo'  => 'required|file|mimes:csv,txt,xlsx,xls,ods|max:10240',
            'grupo_id' => 'nullable|exists:grupos,id',
        ]);

        $ciclo = $request->input('ciclo');
        $area  = $request->input('area');

        $grupoId    = $request->input('grupo_id');
        $schoolYear = SchoolYear::activo()->first();

        $file      = $request->file('archivo');
        $extension = strtolower($file->getClientOriginalExtension());
        $path      = $file->getRealPath();

        $rows     = []; // will hold [ [col0, col1, ...], ... ]
        $cabecera = [];

        // ── Parse file by type ─────────────────────────────────────────────
        $isExcel = in_array($extension, ['xlsx', 'xls', 'ods']);

        if ($isExcel && class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            try {
                $spreadsheet = IOFactory::load($path);
                $sheet       = $spreadsheet->getActiveSheet();
                // $formatData=false → las fechas vienen como serial numérico de Excel;
                // parsearFecha() las convierte correctamente sin depender del locale.
                $sheetData   = $sheet->toArray(null, true, false, false);
                // Remove completely empty rows
                $sheetData = array_values(array_filter($sheetData, function ($r) {
                    return count(array_filter($r, fn($v) => trim((string)$v) !== '')) > 0;
                }));
                if (empty($sheetData)) {
                    return back()->withErrors(['archivo' => 'El archivo Excel está vacío.']);
                }
                $cabecera = array_map(fn($c) => $this->normalizarColumna((string)$c), $sheetData[0]);
                $rows     = array_slice($sheetData, 1);
            } catch (\Exception $e) {
                return back()->withErrors(['archivo' => 'No se pudo leer el archivo Excel: ' . $e->getMessage()]);
            }
        } else {
            // CSV / TXT / TSV
            $rawContent = file_get_contents($path);
            $encoding   = mb_detect_encoding($rawContent, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'UTF-16'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
            }
            $rawContent = ltrim($rawContent, "\xEF\xBB\xBF");

            $tmpPath = tempnam(sys_get_temp_dir(), 'sge_import_');
            file_put_contents($tmpPath, $rawContent);
            $handle = fopen($tmpPath, 'r');

            // Detect delimiter: usar str_getcsv que respeta campos entrecomillados
            $firstLine = rtrim((string) fgets($handle), "\r\n");
            rewind($handle);
            $candidates = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
            foreach (array_keys($candidates) as $d) {
                $candidates[$d] = count(str_getcsv($firstLine, $d));
            }
            arsort($candidates);
            $delimiter = (string) array_key_first($candidates);
            // Si ningún separador produjo más de 1 columna, usar coma como fallback
            if ($candidates[$delimiter] <= 1) $delimiter = ',';

            $headerRow = fgetcsv($handle, 0, $delimiter);
            if (!$headerRow) {
                fclose($handle);
                @unlink($tmpPath);
                return back()->withErrors(['archivo' => 'El archivo está vacío o tiene formato incorrecto.']);
            }
            $cabecera = array_map(fn($c) => $this->normalizarColumna($c), $headerRow);

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
            @unlink($tmpPath);
        }

        $importados = 0;
        $omitidos   = 0;
        $errores    = [];
        $fila       = 1;
        $year       = date('Y');

        foreach ($rows as $row) {
            $fila++;

            // Skip blank rows
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $data = array_combine($cabecera, array_pad(array_map('strval', $row), count($cabecera), ''));

            $nombres   = trim($data['nombres']   ?? '');
            $apellidos = trim($data['apellidos'] ?? '');

            if (!$nombres || !$apellidos) {
                $errores[] = "Fila {$fila}: nombres y apellidos son obligatorios.";
                $omitidos++;
                continue;
            }

            $cedula = trim($data['cedula'] ?? '') ?: null;

            // Skip if cedula already exists
            if ($cedula && Estudiante::where('cedula', $cedula)->exists()) {
                $errores[] = "Fila {$fila}: cédula {$cedula} ya está registrada — omitida.";
                $omitidos++;
                continue;
            }

            // Auto-generate numero_matricula (guarantee uniqueness)
            $numMatricula = trim($data['numero_matricula'] ?? '') ?: null;
            if (!$numMatricula) {
                do {
                    $count = Estudiante::whereYear('created_at', $year)->count() + $importados + 1;
                    $numMatricula = $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
                } while (Estudiante::where('numero_matricula', $numMatricula)->exists());
            } elseif (Estudiante::where('numero_matricula', $numMatricula)->exists()) {
                $errores[] = "Fila {$fila}: número de matrícula {$numMatricula} ya existe — omitida.";
                $omitidos++;
                continue;
            }

            $sexo    = strtoupper(trim($data['sexo'] ?? ''));
            $estado  = trim($data['estado'] ?? '');

            // fecha_nacimiento — opcional, se acepta si viene, se deja null si no
            $fechaRaw = trim($data['fecha_nacimiento'] ?? '');
            $fecha    = $fechaRaw ? $this->parsearFecha($fechaRaw) : null;

            try {
                $estudiante = Estudiante::create([
                    'numero_matricula' => $numMatricula,
                    'cedula'           => $cedula,
                    'nombres'          => $nombres,
                    'apellidos'        => $apellidos,
                    'fecha_nacimiento' => $fecha,
                    'sexo'             => in_array($sexo, ['M','F']) ? $sexo : 'M',
                    'nacionalidad'     => trim($data['nacionalidad'] ?? '') ?: 'Dominicana',
                    'telefono'         => trim($data['telefono']     ?? '') ?: null,
                    'email'            => trim($data['email']        ?? '') ?: null,
                    'direccion'        => trim($data['direccion']    ?? '') ?: null,
                    'sector'           => trim($data['sector']       ?? '') ?: null,
                    'municipio'        => trim($data['municipio']    ?? '') ?: null,
                    'provincia'        => trim($data['provincia']    ?? '') ?: null,
                    'tutor_nombre'     => trim($data['tutor_nombre'] ?? '') ?: null,
                    'tutor_telefono'   => trim($data['tutor_telefono'] ?? '') ?: null,
                    'estado'           => in_array($estado, ['activo','inactivo','egresado','transferido'])
                                         ? $estado : 'activo',
                ]);

                // Optional bulk matriculation
                if ($grupoId && $schoolYear) {
                    $alreadyEnrolled = Matricula::where('estudiante_id', $estudiante->id)
                        ->where('school_year_id', $schoolYear->id)
                        ->exists();
                    if (!$alreadyEnrolled) {
                        Matricula::create([
                            'school_year_id' => $schoolYear->id,
                            'estudiante_id'  => $estudiante->id,
                            'grupo_id'       => $grupoId,
                            'estado'         => 'activa',
                        ]);
                    }
                }

                $importados++;
            } catch (QueryException $e) {
                if ($e->errorInfo[1] === 1062) {
                    $errores[] = "Fila {$fila}: {$nombres} {$apellidos} — registro duplicado, omitido.";
                } else {
                    $errores[] = "Fila {$fila}: {$nombres} {$apellidos} — error al guardar: " . $e->getMessage();
                }
                $omitidos++;
            }
        }

        $msg = "Importación completada: {$importados} estudiante(s) registrado(s).";
        if ($grupoId && $schoolYear) $msg .= " Matriculados en el grupo seleccionado.";
        if ($omitidos) $msg .= " {$omitidos} fila(s) omitida(s).";

        // Redirigir a la lista general (sin filtro de ciclo) para que se vean
        // los estudiantes recién importados — aunque no tengan matrícula aún.
        if ($errores) {
            // Si hay advertencias, volver al formulario para mostrarlas
            return redirect()
                ->route('admin.estudiantes.import', array_filter(['ciclo' => $ciclo, 'area' => $area]))
                ->with('success', $msg)
                ->with('errores_import', $errores);
        }

        return redirect()
            ->route('admin.estudiantes.index')
            ->with('success', $msg);
    }

    // ── Analizar nombre de hoja y determinar grado + sección + grupo ──────
    // Devuelve: [
    //   'grado'          => Grado|null,
    //   'seccion'        => Seccion|null,
    //   'grupo'          => Grupo|null,   ← null = no existe aún
    //   'necesita_crear' => bool,
    //   'label'          => string,       ← texto descriptivo para la UI
    // ]
    private function analizarHoja(string $nombreHoja, $gruposExistentes, $schoolYear): array
    {
        $vacío = ['grado' => null, 'seccion' => null, 'grupo' => null, 'necesita_crear' => false, 'label' => ''];

        $n = mb_strtolower(trim($nombreHoja), 'UTF-8');
        $n = strtr($n, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ñ'=>'n',
            '°'=>'',
        ]);

        // ── 2. Extraer nivel del grado (primero para poder excluir sus letras) ──
        $nivelMap = [
            'primero'=>1,'primer'=>1,'1ro'=>1,'1er'=>1,'1ero'=>1,'1°'=>1,
            'segundo'=>2,'2do'=>2,'2da'=>2,'2°'=>2,
            'tercero'=>3,'tercer'=>3,'3ro'=>3,'3er'=>3,'3°'=>3,
            'cuarto'=>4,'4to'=>4,'4ta'=>4,'4°'=>4,
            'quinto'=>5,'5to'=>5,'5ta'=>5,'5°'=>5,
            'sexto'=>6,'6to'=>6,'6ta'=>6,'6°'=>6,
        ];
        $nivelDetectado = null;
        foreach ($nivelMap as $kw => $nv) {
            if (strpos($n, $kw) !== false) { $nivelDetectado = $nv; break; }
        }
        // Fallback: dígito solo (sin word boundary estricto) que no vaya pegado a otro dígito
        if (!$nivelDetectado && preg_match('/(?<!\d)([1-6])(?!\d)/', $n, $m)) {
            $nivelDetectado = (int)$m[1];
        }

        if (!$nivelDetectado) return $vacío;

        // ── 1. Extraer letra de sección ─────────────────────────────────────
        // Quitar los fragmentos del keyword de nivel para que no interfieran
        $sinNivel = $n;
        foreach ($nivelMap as $kw => $nv) {
            if ($nv === $nivelDetectado) {
                $sinNivel = str_replace($kw, '', $sinNivel);
            }
        }
        $sinNivel = trim($sinNivel);

        $letraSec = null;
        // Letra separada por espacio/símbolo: "6to A", "3ro-B"
        if (preg_match('/(?:^|[\s\-_\/\(])([a-z])(?:[\s\-_\/\)]|$)/i', $sinNivel, $m)) {
            $letraSec = strtoupper($m[1]);
        }
        // Letra al final del texto sin nivel: "6toB" → sinNivel="b" → última letra
        if (!$letraSec && preg_match('/^[^a-z]*([a-z])\s*$/i', $sinNivel, $m)) {
            $letraSec = strtoupper($m[1]);
        }

        // ── Sin letra de sección: intentar usar el único grupo del grado ──────
        if (!$letraSec) {
            $grado = Grado::where('nivel', $nivelDetectado)->first();
            if (!$grado) return $vacío;

            $gruposDelGrado = collect($gruposExistentes)->filter(
                fn ($g) => $g->grado_id === $grado->id
            );

            if ($gruposDelGrado->count() === 1) {
                $g = $gruposDelGrado->first();
                return [
                    'grado'          => $grado,
                    'seccion'        => $g->seccion,
                    'grupo'          => $g,
                    'necesita_crear' => false,
                    'label'          => $grado->nombre . ' — Sección ' . ($g->seccion->nombre ?? 'A'),
                ];
            }
            // Múltiples grupos o ninguno — no se puede determinar cuál
            return $vacío;
        }

        // ── 3. Buscar Grado y Sección en BD (crea la sección si no existe) ────
        $grado   = Grado::where('nivel', $nivelDetectado)->first();
        if (!$grado) return $vacío;

        $seccion = Seccion::firstOrCreate(
            ['nombre' => $letraSec],
            ['orden'  => ord($letraSec) - ord('A') + 1]
        );

        // ── 4. Verificar si ya existe el grupo ──────────────────────────────
        $grupoExistente = null;

        // Buscar primero entre los ya cargados (para evitar N+1)
        foreach ($gruposExistentes as $g) {
            if ($g->grado_id == $grado->id && $g->seccion_id == $seccion->id) {
                $grupoExistente = $g; break;
            }
        }

        // Si no estaba en la colección (puede que sea de otro año), buscar en BD
        if (!$grupoExistente && $schoolYear) {
            $grupoExistente = Grupo::where('grado_id', $grado->id)
                ->where('seccion_id', $seccion->id)
                ->where('school_year_id', $schoolYear->id)
                ->first();
        }

        $label = $grado->nombre . ' — Sección ' . $seccion->nombre;

        return [
            'grado'          => $grado,
            'seccion'        => $seccion,
            'grupo'          => $grupoExistente,
            'necesita_crear' => $grupoExistente === null,
            'label'          => $label,
        ];
    }

    // ── Normalizar nombre de columna (cabecera) ────────────────────────────
    // Convierte "Fecha de Nacimiento", "Sección", "No.", etc. a claves internas
    private function normalizarColumna(string $col): string
    {
        $col = mb_strtolower(trim($col), 'UTF-8');
        // Reemplazar tildes / caracteres especiales
        $col = strtr($col, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
            'ò' => 'o', 'ù' => 'u',
        ]);
        // Espacios y puntos → guión bajo
        $col = preg_replace('/[\s.]+/', '_', $col);
        // Eliminar cualquier otro carácter no alfanumérico/guión bajo
        $col = preg_replace('/[^\w]/', '', $col);
        $col = trim($col, '_');

        // Mapa de alias → clave interna usada en importStore
        $aliases = [
            'fecha_de_nacimiento'  => 'fecha_nacimiento',
            'fechadenacimiento'    => 'fecha_nacimiento',
            'fecha_nacimiento'     => 'fecha_nacimiento',
            'apellido'             => 'apellidos',
            'nombre'               => 'nombres',
            'cedula'               => 'cedula',
            'cdula'                => 'cedula',
            'telefono'             => 'telefono',
            'telfono'              => 'telefono',
            'tel_tutor'            => 'tutor_telefono',
            'nombre_del_tutor'     => 'tutor_nombre',
            'nombredeltutor'       => 'tutor_nombre',
            'tutor_nombre'         => 'tutor_nombre',
            'tutor_telefono'       => 'tutor_telefono',
            'direccion'            => 'direccion',
            'direccin'             => 'direccion',
            'nacionalidad'         => 'nacionalidad',
            'estado'               => 'estado',
            'sexo'                 => 'sexo',
            'email'                => 'email',
            'sector'               => 'sector',
            'municipio'            => 'municipio',
            'provincia'            => 'provincia',
            'apellidos'            => 'apellidos',
            'nombres'              => 'nombres',
            // Columnas informativas — se ignoran en importStore
            'no'                   => '_no',
            'n'                    => '_no',
            'seccion'              => '_seccion',
            'seccin'               => '_seccion',
            'seccion_'             => '_seccion',
        ];

        return $aliases[$col] ?? $col;
    }

    // ── Parse fecha_nacimiento (múltiples formatos) ────────────────────────
    // Soporta: DD/MM/YYYY, YYYY-MM-DD, D/M/YYYY, serial Excel, datetime strings.
    private function parsearFecha(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // Serial numérico de Excel (p.ej. 44567 → 2022-01-07)
        if (is_numeric($raw) && $raw > 1000 && $raw < 200000) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);
                $y  = (int) $dt->format('Y');
                if ($y >= 1900 && $y <= 2099) return $dt->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        // Tomar solo los primeros 10 caracteres (descarta hora si viene como datetime)
        $part = substr($raw, 0, 10);

        // Formatos ordenados del más específico al más genérico
        $formatos = [
            'Y-m-d',   // 2010-05-15  (ISO — plantilla)
            'd/m/Y',   // 15/05/2010  (dominicano / español)
            'd-m-Y',   // 15-05-2010
            'd.m.Y',   // 15.05.2010
            'Y/m/d',   // 2010/05/15
            'm/d/Y',   // 05/15/2010  (US — menos común)
        ];

        foreach ($formatos as $fmt) {
            $dt = \DateTime::createFromFormat('!' . $fmt, $part);
            if ($dt !== false) {
                $y = (int) $dt->format('Y');
                if ($y >= 1900 && $y <= 2099) return $dt->format('Y-m-d');
            }
        }

        // Último recurso: strtotime (maneja "May 15, 2010" y similares)
        $ts = strtotime($raw);
        if ($ts !== false && $ts > 0) {
            $y = (int) date('Y', $ts);
            if ($y >= 1900 && $y <= 2099) return date('Y-m-d', $ts);
        }

        return null;
    }

    // ── Ficha del estudiante PDF ──────────────────────────────────────────
    public function fichaPdf(Estudiante $estudiante)
    {
        $estudiante->load([
            'representantes',
            'matriculas.schoolYear',
            'matriculas.grupo.grado',
            'matriculas.grupo.seccion',
        ]);

        $sy     = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;
        $si     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.estudiantes.ficha_pdf',
            compact('estudiante', 'config', 'si', 'sy')
        )->setPaper('letter', 'portrait');

        return $pdf->download('ficha_' . Str::slug($estudiante->nombre_completo ?? 'estudiante') . '.pdf');
    }

    // ── Representantes Excel ──────────────────────────────────────────────
    public function representantesExcel()
    {
        $sy = SchoolYear::actual();

        $representantes = \App\Models\Representante::with([
            'estudiantes' => fn($q) => $q->with([
                'matriculas' => fn($m) => $m->where('estado', 'activa')
                    ->when($sy, fn($m) => $m->where('school_year_id', $sy->id))
                    ->with(['grupo.grado', 'grupo.seccion']),
            ]),
        ])->orderBy('apellidos')->orderBy('nombres')->get();

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Representantes');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'DIRECTORIO DE REPRESENTANTES — ' . ($sy?->nombre ?? date('Y')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Apellidos', 'Nombres', 'Cédula', 'Teléfono', 'Email', 'Ocupación', 'Hijo(s)', 'Grupo(s)'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:I2')->applyFromArray($hdrStyle);

        foreach ($representantes as $i => $rep) {
            $row = $i + 3;

            $hijos  = $rep->estudiantes->map(fn($e) => trim($e->apellidos . ' ' . $e->nombres))->implode('; ');
            $grupos = $rep->estudiantes->flatMap(fn($e) => $e->matriculas->map(fn($m) => $m->grupo
                ? ($m->grupo->grado->nombre ?? '') . ' ' . ($m->grupo->seccion->nombre ?? '')
                : '')
            )->filter()->unique()->implode('; ');

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $rep->apellidos ?? '');
            $sheet->setCellValue("C{$row}", $rep->nombres   ?? '');
            $sheet->setCellValue("D{$row}", $rep->cedula    ?? '');
            $sheet->setCellValue("E{$row}", $rep->telefono  ?? '');
            $sheet->setCellValue("F{$row}", $rep->email     ?? '');
            $sheet->setCellValue("G{$row}", $rep->ocupacion ?? '');
            $sheet->setCellValue("H{$row}", $hijos);
            $sheet->setCellValue("I{$row}", $grupos);

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'I') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new XlsxWriter($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'rep_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'representantes_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista de Representantes PDF ───────────────────────────────────────
    public function representantesPdf()
    {
        $sy = SchoolYear::actual();

        $representantes = \App\Models\Representante::with([
            'estudiantes' => fn($q) => $q->with([
                'matriculas' => fn($m) => $m->where('estado', 'activa')
                    ->when($sy, fn($m2) => $m2->where('school_year_id', $sy->id))
                    ->with(['grupo.grado', 'grupo.seccion']),
            ]),
        ])->orderBy('apellidos')->orderBy('nombres')->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.estudiantes.representantes_pdf', compact(
            'representantes', 'sy', 'inst'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('Representantes_' . ($sy?->nombre ?? date('Y')) . '.pdf');
    }

    // ── Private helpers ────────────────────────────────────────────────────
    private function procesarFoto($file, string $directory): string
    {
        $filename    = Str::uuid() . '.jpg';
        $storagePath = $directory . '/' . $filename;

        $img = Image::make($file)->fit(300, 300)->encode('jpg', 85);

        Storage::disk('public')->put($storagePath, (string) $img);

        return $storagePath;
    }

    // ── Lista de estudiantes Excel ────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $sy     = SchoolYear::actual();
        $ciclo  = $request->ciclo;
        $buscar = $request->buscar;

        $query = Estudiante::with([
            'matriculas' => fn($q) => $q->with(['grupo.grado', 'grupo.seccion'])
                ->where('estado', 'activa')
                ->when($sy, fn($q) => $q->where('school_year_id', $sy->id)),
            'representantes',
        ])
        ->when($buscar, fn($q) => $q->where(fn($s) => $s
            ->where('nombres', 'like', "%{$buscar}%")
            ->orWhere('apellidos', 'like', "%{$buscar}%")
            ->orWhere('numero_matricula', 'like', "%{$buscar}%")
        ))
        ->when($ciclo, function ($q) use ($ciclo) {
            $q->whereHas('matriculas.grupo.grado', fn($s) => $s->where('ciclo', $ciclo));
        })
        ->orderBy('apellidos')
        ->get();

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Estudiantes');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'DIRECTORIO DE ESTUDIANTES — ' . ($sy?->nombre ?? date('Y')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Matrícula', 'Apellidos', 'Nombres', 'Cédula', 'Fecha Nac.', 'Sexo', 'Grupo Actual', 'Representante', 'Tel. Rep.', 'Estado'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '2';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A2:K2')->applyFromArray($hdrStyle);

        foreach ($query as $i => $est) {
            $row       = $i + 3;
            $matricula = $est->matriculas->first();
            $rep       = $est->representantes->first();

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $est->numero_matricula ?? '');
            $sheet->setCellValue("C{$row}", $est->apellidos ?? $est->apellido ?? '');
            $sheet->setCellValue("D{$row}", $est->nombres   ?? $est->nombre  ?? '');
            $sheet->setCellValue("E{$row}", $est->cedula ?? '');
            $sheet->setCellValue("F{$row}", $est->fecha_nacimiento ? \Carbon\Carbon::parse($est->fecha_nacimiento)->format('d/m/Y') : '');
            $sheet->setCellValue("G{$row}", $est->sexo ?? '');
            $sheet->setCellValue("H{$row}", $matricula?->grupo
                ? ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '')
                : '');
            $sheet->setCellValue("I{$row}", $rep ? trim(($rep->nombres ?? $rep->nombre ?? '') . ' ' . ($rep->apellidos ?? $rep->apellido ?? '')) : '');
            $sheet->setCellValue("J{$row}", $rep?->celular ?? $rep?->telefono ?? '');
            $sheet->setCellValue("K{$row}", $est->activo ? 'Activo' : 'Inactivo');

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:K{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'K') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new XlsxWriter($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'est_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'estudiantes_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista general PDF ────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $sy     = SchoolYear::actual();
        $ciclo  = $request->ciclo;
        $buscar = $request->buscar;

        $estudiantes = Estudiante::with([
            'matriculas' => fn($q) => $q->with(['grupo.grado', 'grupo.seccion'])
                ->where('estado', 'activa')
                ->when($sy, fn($q) => $q->where('school_year_id', $sy->id)),
            'representantes',
        ])
        ->when($buscar, fn($q) => $q->where(fn($s) => $s
            ->where('nombres', 'like', "%{$buscar}%")
            ->orWhere('apellidos', 'like', "%{$buscar}%")
            ->orWhere('numero_matricula', 'like', "%{$buscar}%")
        ))
        ->when($ciclo, function ($q) use ($ciclo) {
            $q->whereHas('matriculas.grupo.grado', fn($s) => $s->where('ciclo', $ciclo));
        })
        ->orderBy('apellidos')
        ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.estudiantes.lista_pdf',
            compact('estudiantes', 'inst', 'sy', 'ciclo')
        )->setPaper('letter', 'landscape');

        return $pdf->download('estudiantes_' . now()->format('Ymd') . '.pdf');
    }
}
