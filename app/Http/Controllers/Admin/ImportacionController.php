<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\CalificacionAcademica;
use App\Models\Calificacion;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * ImportacionController
 *
 * Hub central de importaciones masivas.
 * Expone dos módulos:
 *
 *   1. Calificaciones académicas (comp1_p1 … comp4_p4) por asignación.
 *   2. Lista de estudiantes con matrícula opcional.
 *
 * La lógica de parseo CSV/Excel usa PHP nativo + PhpSpreadsheet (ya disponible).
 * NO depende de Maatwebsite/Excel.
 */
class ImportacionController extends Controller
{
    // ═══════════════════════════════════════════════════════════════════════
    //  HUB PRINCIPAL
    // ═══════════════════════════════════════════════════════════════════════

    /** GET /admin/importaciones — pantalla de bienvenida / selección de módulo */
    public function index()
    {
        $schoolYear = SchoolYear::actual();
        return view('admin.importaciones.index', compact('schoolYear'));
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  MÓDULO 1 — CALIFICACIONES ACADÉMICAS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * GET /admin/importaciones/calificaciones
     * Formulario de importación de calificaciones académicas (4 comp × 4 períodos).
     */
    public function calificacionesForm(Request $request)
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo configurado.');
        }

        $asignaciones = Asignacion::with(['grupo.grado', 'grupo.seccion', 'asignatura'])
            ->where('school_year_id', $schoolYear->id)
            ->where('area', 'academica')
            ->where('activo', true)
            ->get()
            ->sortBy(fn($a) =>
                ($a->grupo->grado->nombre ?? '') . ' ' .
                ($a->grupo->seccion->nombre ?? '') . ' ' .
                ($a->asignatura->nombre ?? '')
            );

        return view('admin.importaciones.calificaciones', compact('schoolYear', 'asignaciones'));
    }

    /**
     * GET /admin/importaciones/calificaciones/plantilla
     * Descarga plantilla CSV/XLSX con columnas académicas pre-rellenadas.
     */
    public function calificacionesPlantilla(Request $request)
    {
        $asignacionId = $request->asignacion_id;
        $format       = $request->input('format', 'xlsx');

        $asignacion = $asignacionId
            ? Asignacion::with(['asignatura', 'grupo.grado', 'grupo.seccion'])->find($asignacionId)
            : null;

        // Columnas: número de matrícula + cédula + nombres ref + 16 notas
        $headers = [
            'numero_matricula', 'cedula', 'nombres', 'apellidos',
            'comp1_p1', 'comp2_p1', 'comp3_p1', 'comp4_p1',
            'comp1_p2', 'comp2_p2', 'comp3_p2', 'comp4_p2',
            'comp1_p3', 'comp2_p3', 'comp3_p3', 'comp4_p3',
            'comp1_p4', 'comp2_p4', 'comp3_p4', 'comp4_p4',
        ];

        $rows = [];

        if ($asignacion) {
            $matriculas = $asignacion->grupo->matriculas()
                ->activas()
                ->with('estudiante')
                ->orderBy('numero_orden')
                ->get();

            // Pre-cargar notas existentes
            $schoolYear = SchoolYear::actual();
            $notasExistentes = CalificacionAcademica::where('asignacion_id', $asignacion->id)
                ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
                ->get()
                ->keyBy('matricula_id');

            foreach ($matriculas as $mat) {
                $nota = $notasExistentes->get($mat->id);
                $row  = [
                    $mat->numero_matricula ?? '',
                    $mat->estudiante->cedula    ?? '',
                    $mat->estudiante->nombres   ?? '',
                    $mat->estudiante->apellidos ?? '',
                ];
                foreach (['p1','p2','p3','p4'] as $p) {
                    $pNum = substr($p, 1);
                    foreach ([1,2,3,4] as $c) {
                        $row[] = $nota ? ($nota->{"comp{$c}_p{$pNum}"} ?? '') : '';
                    }
                }
                $rows[] = $row;
            }
        }

        if (empty($rows)) {
            // Filas de ejemplo genéricas
            $rows[] = array_merge(['2024-00001', '001-1234567-8', 'Juan',  'Pérez'],    array_fill(0, 16, 85));
            $rows[] = array_merge(['2024-00002', '001-2345678-9', 'María', 'González'], array_fill(0, 16, 92));
        }

        $nombreBase = 'plantilla_calificaciones_academicas'
            . ($asignacion ? '_' . \Illuminate\Support\Str::slug($asignacion->asignatura->nombre ?? 'asignatura') : '');

        if ($format === 'xlsx') {
            return $this->generarPlantillaXlsx($headers, $rows, $nombreBase, $asignacion);
        }

        // CSV con BOM UTF-8
        $csv = "\xEF\xBB\xBF" . implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', (string) $v) . '"',
                $row
            )) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreBase}.csv\"",
        ]);
    }

    /**
     * POST /admin/importaciones/calificaciones
     * Procesa el archivo CSV/XLSX y guarda las calificaciones académicas.
     */
    public function calificacionesImportar(Request $request)
    {
        $request->validate([
            'archivo'       => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'asignacion_id' => 'required|exists:asignaciones,id',
        ]);

        $asignacion = Asignacion::with(['asignatura', 'grupo'])->findOrFail($request->asignacion_id);
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo.');
        }

        // ── Leer filas del archivo ──────────────────────────────────────
        $rows = $this->leerArchivo($request->file('archivo'));

        if ($rows === null) {
            return back()->with('error', 'No se pudo leer el archivo. Verifica el formato.');
        }

        // Pre-cargar matrículas activas del grupo
        $matriculasPorNum    = $asignacion->grupo->matriculas()
            ->activas()->with('estudiante')->get()->keyBy('numero_matricula');
        $matriculasPorCedula = $matriculasPorNum->groupBy(fn($m) => $m->estudiante->cedula ?? '');

        $importados = 0;
        $omitidos   = 0;
        $errores    = [];
        $resultados = [];

        foreach ($rows as $i => $row) {
            $linea   = $i + 2;
            $numMat  = trim($row['numero_matricula'] ?? $row['num_matricula'] ?? '');
            $cedula  = trim($row['cedula'] ?? '');

            $matricula = null;
            if ($numMat && $matriculasPorNum->has($numMat)) {
                $matricula = $matriculasPorNum->get($numMat);
            } elseif ($cedula && $matriculasPorCedula->has($cedula)) {
                $matricula = $matriculasPorCedula->get($cedula)->first();
            }

            if (! $matricula) {
                $errores[]    = "Fila {$linea}: Estudiante no encontrado (matrícula: '{$numMat}', cédula: '{$cedula}').";
                $resultados[] = ['fila' => $linea, 'estado' => 'error', 'mensaje' => 'Estudiante no encontrado', 'nombre' => "{$numMat}/{$cedula}"];
                $omitidos++;
                continue;
            }

            $nombre = trim(($matricula->estudiante->apellidos ?? '') . ', ' . ($matricula->estudiante->nombres ?? ''));

            // Construir datos de notas (comp1_p1 … comp4_p4)
            $data        = [];
            $tieneDatos  = false;
            $notasValidas = true;
            $notaErrMsg  = [];

            foreach (['p1','p2','p3','p4'] as $pIdx => $pKey) {
                $pNum = $pIdx + 1;
                foreach ([1,2,3,4] as $c) {
                    // Acepta ambas convenciones: comp1_p1 y p1_comp1
                    $val = trim(
                        $row["comp{$c}_{$pKey}"] ??
                        $row["{$pKey}_comp{$c}"] ??
                        ''
                    );

                    if ($val === '' || $val === null) {
                        continue;
                    }

                    if (! is_numeric($val)) {
                        $notasValidas = false;
                        $notaErrMsg[] = "comp{$c}_p{$pNum}='{$val}' no es numérico";
                        continue;
                    }

                    $nota = (float) $val;
                    if ($nota < 0 || $nota > 100) {
                        $notasValidas = false;
                        $notaErrMsg[] = "comp{$c}_p{$pNum}={$nota} fuera de rango [0-100]";
                        continue;
                    }

                    $data["comp{$c}_p{$pNum}"] = $nota;
                    $tieneDatos = true;
                }
            }

            if (! empty($notaErrMsg)) {
                $errores[]    = "Fila {$linea} ({$nombre}): " . implode('; ', $notaErrMsg) . " — valores inválidos omitidos.";
                $resultados[] = ['fila' => $linea, 'estado' => 'advertencia', 'mensaje' => implode('; ', $notaErrMsg), 'nombre' => $nombre];
            }

            if (! $tieneDatos) {
                $errores[]    = "Fila {$linea} ({$nombre}): Sin datos numéricos válidos — fila omitida.";
                $resultados[] = ['fila' => $linea, 'estado' => 'error', 'mensaje' => 'Sin datos válidos', 'nombre' => $nombre];
                $omitidos++;
                continue;
            }

            try {
                /** @var CalificacionAcademica $calAcad */
                $calAcad = CalificacionAcademica::updateOrCreate(
                    [
                        'matricula_id'  => $matricula->id,
                        'asignacion_id' => $asignacion->id,
                        'school_year_id'=> $schoolYear->id,
                    ],
                    array_merge($data, ['modificado_por' => auth()->id()])
                );

                // Recalcular promedios derivados
                $calAcad->recalcularPromedios();

                $importados++;
                $resultados[] = ['fila' => $linea, 'estado' => 'ok', 'mensaje' => 'Importado', 'nombre' => $nombre];
            } catch (\Throwable $e) {
                $errores[]    = "Fila {$linea} ({$nombre}): Error al guardar — " . $e->getMessage();
                $resultados[] = ['fila' => $linea, 'estado' => 'error', 'mensaje' => 'Error al guardar', 'nombre' => $nombre];
                $omitidos++;
            }
        }

        $msg = "Se importaron {$importados} calificación(es) correctamente.";
        if ($omitidos) {
            $msg .= " {$omitidos} fila(s) omitida(s).";
        }

        return back()
            ->with('success', $msg)
            ->with('errores_import', $errores)
            ->with('resultados_import', $resultados)
            ->with('stats_import', ['importados' => $importados, 'omitidos' => $omitidos, 'total' => count($rows)]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  MÓDULO 2 — ESTUDIANTES
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * GET /admin/importaciones/estudiantes
     * Formulario de importación masiva de estudiantes.
     */
    public function estudiantesForm(Request $request)
    {
        $schoolYear = SchoolYear::activo()->first();

        $grupos = Grupo::with(['grado', 'seccion'])
            ->join('grados',    'grados.id',    '=', 'grupos.grado_id')
            ->join('secciones', 'secciones.id', '=', 'grupos.seccion_id')
            ->when($schoolYear, fn($q) => $q->where('grupos.school_year_id', $schoolYear->id))
            ->orderBy('grados.nivel')
            ->orderBy('secciones.nombre')
            ->select('grupos.*')
            ->get();

        return view('admin.importaciones.estudiantes', compact('grupos', 'schoolYear'));
    }

    /**
     * GET /admin/importaciones/estudiantes/plantilla
     * Descarga plantilla CSV/XLSX para importación de estudiantes.
     */
    public function estudiantesPlantilla(Request $request)
    {
        $format = $request->input('format', 'xlsx');

        $headers = [
            'nombres', 'apellidos', 'cedula', 'fecha_nacimiento',
            'sexo', 'direccion', 'nombre_representante',
            'telefono_representante', 'email_representante',
        ];

        $rows = [
            ['Juan Carlos', 'Pérez Martínez',    '001-1234567-8', '2010-05-15', 'M', 'Calle 5 No. 10', 'María Martínez', '809-555-0001', 'mmartinez@email.com'],
            ['Ana Sofía',   'González López',    '001-2345678-9', '2011-03-22', 'F', 'Av. Lincoln 45',  'Pedro González',  '809-555-0002', ''],
            ['Luis Miguel', 'Ramírez Castillo',  '',              '2009-11-08', 'M', '',               'Carmen Castillo', '809-555-0003', 'ccastillo@email.com'],
        ];

        if ($format === 'xlsx') {
            $ss    = new Spreadsheet();
            $sheet = $ss->getActiveSheet();
            $sheet->setTitle('Plantilla Estudiantes');

            // Cabecera
            $hdrStyle = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->fromArray([$headers], null, 'A1');
            $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1')
                ->applyFromArray($hdrStyle);

            // Columnas opcionales en gris
            $optStyle = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f3f4f6']],
                'font' => ['color' => ['rgb' => '6b7280']],
            ];
            $sheet->getStyle('C1:I1')->applyFromArray($optStyle);
            // Obligs en azul claro (sobre el azul oscuro ya aplicado) — reforzamos las dos primeras
            $reqStyle = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            ];
            $sheet->getStyle('A1:B1')->applyFromArray($reqStyle);

            // Datos de ejemplo
            foreach ($rows as $rowIdx => $row) {
                $sheet->fromArray([$row], null, 'A' . ($rowIdx + 2));
            }

            // Auto-width
            foreach (range(1, count($headers)) as $ci) {
                $sheet->getColumnDimensionByColumn($ci)->setAutoSize(true);
            }

            // Leyenda
            $noteRow = count($rows) + 3;
            $sheet->setCellValue("A{$noteRow}", 'NOTAS:');
            $sheet->setCellValue("B{$noteRow}",
                'nombres y apellidos son OBLIGATORIOS. ' .
                'cedula: omite la fila si ya existe. ' .
                'sexo = M o F. ' .
                'fecha_nacimiento: AAAA-MM-DD o DD/MM/AAAA. ' .
                'email_representante: crea cuenta de acceso al portal.'
            );
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
            $sheet->mergeCells("B{$noteRow}:{$lastCol}{$noteRow}");
            $sheet->getStyle("A{$noteRow}:B{$noteRow}")->getFont()->setItalic(true)->setSize(9);

            $sheet->freezePane('A2');

            $writer  = new Xlsx($ss);
            $tmpFile = tempnam(sys_get_temp_dir(), 'est_') . '.xlsx';
            $writer->save($tmpFile);

            return response()->download($tmpFile, 'plantilla_estudiantes.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // CSV
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ',');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',');
            }
            fputcsv($out, ['# nombres y apellidos son obligatorios. cedula: omite duplicados. sexo=M/F. fecha_nacimiento: AAAA-MM-DD. email_representante: crea cuenta portal.'], ',');
            fclose($out);
        }, 'plantilla_estudiantes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * POST /admin/importaciones/estudiantes
     * Procesa el CSV/XLSX y crea Estudiante + Matricula por cada fila válida.
     */
    public function estudiantesImportar(Request $request)
    {
        $request->validate([
            'archivo'   => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'grupo_id'  => 'nullable|exists:grupos,id',
            'school_year_id' => 'nullable|exists:school_years,id',
        ]);

        $grupoId    = $request->grupo_id;
        $schoolYear = $request->school_year_id
            ? \App\Models\SchoolYear::find($request->school_year_id)
            : SchoolYear::activo()->first();

        $rows = $this->leerArchivo($request->file('archivo'));

        if ($rows === null) {
            return back()->with('error', 'No se pudo leer el archivo. Verifica el formato.');
        }

        $importados = 0;
        $omitidos   = 0;
        $errores    = [];
        $resultados = [];
        $year       = date('Y');

        foreach ($rows as $i => $row) {
            $fila      = $i + 2;
            $nombres   = trim($row['nombres']   ?? $row['nombre']   ?? '');
            $apellidos = trim($row['apellidos'] ?? $row['apellido'] ?? '');

            if (! $nombres || ! $apellidos) {
                $errores[]    = "Fila {$fila}: nombres y apellidos son obligatorios.";
                $resultados[] = ['fila' => $fila, 'estado' => 'error', 'mensaje' => 'nombres/apellidos vacíos', 'nombre' => '—'];
                $omitidos++;
                continue;
            }

            $nombreDisplay = "{$apellidos}, {$nombres}";

            // Validar cédula duplicada
            $cedula = trim($row['cedula'] ?? '') ?: null;
            if ($cedula && Estudiante::where('cedula', $cedula)->exists()) {
                $errores[]    = "Fila {$fila} ({$nombreDisplay}): cédula {$cedula} ya registrada — omitida.";
                $resultados[] = ['fila' => $fila, 'estado' => 'error', 'mensaje' => "Cédula {$cedula} duplicada", 'nombre' => $nombreDisplay];
                $omitidos++;
                continue;
            }

            // Auto-generar número de matrícula
            $numMatricula = trim($row['numero_matricula'] ?? '') ?: null;
            if (! $numMatricula) {
                do {
                    $count        = Estudiante::whereYear('created_at', $year)->count() + $importados + 1;
                    $numMatricula = $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
                } while (Estudiante::where('numero_matricula', $numMatricula)->exists());
            } elseif (Estudiante::where('numero_matricula', $numMatricula)->exists()) {
                $errores[]    = "Fila {$fila} ({$nombreDisplay}): número de matrícula '{$numMatricula}' ya existe — omitida.";
                $resultados[] = ['fila' => $fila, 'estado' => 'error', 'mensaje' => "Matrícula '{$numMatricula}' duplicada", 'nombre' => $nombreDisplay];
                $omitidos++;
                continue;
            }

            $sexo    = strtoupper(trim($row['sexo'] ?? ''));
            $estado  = trim($row['estado'] ?? 'activo');
            $fechaRaw = trim($row['fecha_nacimiento'] ?? $row['fecha_de_nacimiento'] ?? '');
            $fecha    = $fechaRaw ? $this->parsearFecha($fechaRaw) : null;

            // Datos del representante (columnas del CSV de importación)
            $nombreRep  = trim($row['nombre_representante']  ?? $row['tutor_nombre']   ?? '');
            $telefonoRep = trim($row['telefono_representante'] ?? $row['tutor_telefono'] ?? '');
            $emailRep   = trim($row['email_representante']   ?? '') ?: null;

            try {
                DB::beginTransaction();

                $estudiante = Estudiante::create([
                    'numero_matricula' => $numMatricula,
                    'cedula'           => $cedula,
                    'nombres'          => $nombres,
                    'apellidos'        => $apellidos,
                    'fecha_nacimiento' => $fecha,
                    'sexo'             => in_array($sexo, ['M', 'F']) ? $sexo : 'M',
                    'direccion'        => trim($row['direccion']    ?? '') ?: null,
                    'tutor_nombre'     => $nombreRep ?: null,
                    'tutor_telefono'   => $telefonoRep ?: null,
                    'estado'           => in_array($estado, ['activo','inactivo','egresado','transferido']) ? $estado : 'activo',
                ]);

                // Crear User si viene email de representante
                $cuentaCreada = false;
                if ($emailRep && ! \App\Models\User::where('email', $emailRep)->exists()) {
                    $user = \App\Models\User::create([
                        'name'     => $nombreRep ?: "{$nombres} {$apellidos} (Rep.)",
                        'email'    => $emailRep,
                        'password' => bcrypt(\Illuminate\Support\Str::random(12)),
                    ]);
                    // Asignar rol Representante si existe
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        $rol = \Spatie\Permission\Models\Role::where('name', 'Representante')->first();
                        if ($rol) {
                            $user->assignRole($rol);
                        }
                    }
                    $cuentaCreada = true;
                }

                // Matrícula en el grupo seleccionado
                if ($grupoId && $schoolYear) {
                    $yaMatriculado = Matricula::where('estudiante_id', $estudiante->id)
                        ->where('school_year_id', $schoolYear->id)
                        ->exists();

                    if (! $yaMatriculado) {
                        Matricula::create([
                            'school_year_id'  => $schoolYear->id,
                            'estudiante_id'   => $estudiante->id,
                            'grupo_id'        => $grupoId,
                            'fecha_matricula' => now()->toDateString(),
                            'estado'          => 'activa',
                        ]);
                    }
                }

                DB::commit();

                $importados++;
                $extras       = $cuentaCreada ? ' (cuenta Portal creada)' : '';
                $resultados[] = ['fila' => $fila, 'estado' => 'ok', 'mensaje' => "Importado{$extras}", 'nombre' => $nombreDisplay];

            } catch (QueryException $e) {
                DB::rollBack();
                if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
                    $errores[]    = "Fila {$fila} ({$nombreDisplay}): registro duplicado — omitido.";
                    $resultados[] = ['fila' => $fila, 'estado' => 'error', 'mensaje' => 'Duplicado', 'nombre' => $nombreDisplay];
                } else {
                    $errores[]    = "Fila {$fila} ({$nombreDisplay}): error al guardar.";
                    $resultados[] = ['fila' => $fila, 'estado' => 'error', 'mensaje' => 'Error al guardar', 'nombre' => $nombreDisplay];
                }
                $omitidos++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $errores[]    = "Fila {$fila} ({$nombreDisplay}): " . $e->getMessage();
                $resultados[] = ['fila' => $fila, 'estado' => 'error', 'mensaje' => $e->getMessage(), 'nombre' => $nombreDisplay];
                $omitidos++;
            }
        }

        $msg = "Se importaron {$importados} estudiante(s) correctamente.";
        if ($grupoId && $schoolYear) {
            $msg .= ' Matriculados en el grupo seleccionado.';
        }
        if ($omitidos) {
            $msg .= " {$omitidos} fila(s) omitida(s).";
        }

        return back()
            ->with('success', $msg)
            ->with('errores_import', $errores)
            ->with('resultados_import', $resultados)
            ->with('stats_import', ['importados' => $importados, 'omitidos' => $omitidos, 'total' => count($rows)]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Lee un archivo subido (CSV/TXT o XLSX/XLS) y devuelve un array
     * de filas asociativas [columna => valor].
     * Retorna null si falla la lectura.
     *
     * @return array<int, array<string, string>>|null
     */
    private function leerArchivo(\Illuminate\Http\UploadedFile $file): ?array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        // ── Excel / ODS ────────────────────────────────────────────────
        if (in_array($ext, ['xlsx', 'xls', 'ods'])) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $sheet       = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

                // Eliminar filas completamente vacías
                $sheet = array_values(array_filter($sheet, fn($r) =>
                    count(array_filter($r, fn($v) => trim((string) $v) !== '')) > 0
                ));

                if (count($sheet) < 2) {
                    return [];
                }

                $header = array_map(fn($c) => $this->normalizarColumna((string) $c), $sheet[0]);
                $rows   = [];

                foreach (array_slice($sheet, 1) as $rawRow) {
                    if (count(array_filter($rawRow, fn($v) => trim((string) $v) !== '')) === 0) {
                        continue;
                    }
                    $rows[] = array_combine(
                        $header,
                        array_pad(array_map('strval', $rawRow), count($header), '')
                    );
                }

                return $rows;
            } catch (\Throwable $e) {
                return null;
            }
        }

        // ── CSV / TXT ──────────────────────────────────────────────────
        $raw      = file_get_contents($file->getPathname());
        $encoding = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
        }
        $raw = ltrim($raw, "\xEF\xBB\xBF");

        $tmpPath = tempnam(sys_get_temp_dir(), 'sge_imp_');
        file_put_contents($tmpPath, $raw);
        $handle = fopen($tmpPath, 'r');

        // Detectar delimitador
        $firstLine  = rtrim((string) fgets($handle), "\r\n");
        rewind($handle);
        $candidates = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
        foreach (array_keys($candidates) as $d) {
            $candidates[$d] = count(str_getcsv($firstLine, $d));
        }
        arsort($candidates);
        $delim = (string) array_key_first($candidates);
        if ($candidates[$delim] <= 1) {
            $delim = ',';
        }

        $headerRow = fgetcsv($handle, 0, $delim);
        if (! $headerRow) {
            fclose($handle);
            @unlink($tmpPath);
            return null;
        }

        $header = array_map(fn($c) => $this->normalizarColumna((string) $c), $headerRow);
        $rows   = [];

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine(
                $header,
                array_pad(array_map('strval', $row), count($header), '')
            );
        }

        fclose($handle);
        @unlink($tmpPath);

        return $rows;
    }

    /**
     * Normaliza un nombre de columna: minúsculas, sin tildes, espacios → guión bajo.
     */
    private function normalizarColumna(string $col): string
    {
        $col = mb_strtolower(trim($col), 'UTF-8');
        $col = strtr($col, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
        ]);
        $col = preg_replace('/[\s.]+/', '_', $col);
        $col = preg_replace('/[^\w]/', '', $col);
        $col = trim($col, '_');

        $aliases = [
            'fecha_de_nacimiento' => 'fecha_nacimiento',
            'fechadenacimiento'   => 'fecha_nacimiento',
            'apellido'            => 'apellidos',
            'nombre'              => 'nombres',
            'cdula'               => 'cedula',
            'tel'                 => 'telefono',
            'telfono'             => 'telefono',
            'num_matricula'       => 'numero_matricula',
            'n_matricula'         => 'numero_matricula',
            'matricula'           => 'numero_matricula',
            'no_matricula'        => 'numero_matricula',
            'nombre_del_representante' => 'nombre_representante',
            'tel_representante'   => 'telefono_representante',
            'email_rep'           => 'email_representante',
            'correo_representante'=> 'email_representante',
            'p1_comp1'            => 'comp1_p1',
            'p1_comp2'            => 'comp2_p1',
            'p1_comp3'            => 'comp3_p1',
            'p1_comp4'            => 'comp4_p1',
            'p2_comp1'            => 'comp1_p2',
            'p2_comp2'            => 'comp2_p2',
            'p2_comp3'            => 'comp3_p2',
            'p2_comp4'            => 'comp4_p2',
            'p3_comp1'            => 'comp1_p3',
            'p3_comp2'            => 'comp2_p3',
            'p3_comp3'            => 'comp3_p3',
            'p3_comp4'            => 'comp4_p3',
            'p4_comp1'            => 'comp1_p4',
            'p4_comp2'            => 'comp2_p4',
            'p4_comp3'            => 'comp3_p4',
            'p4_comp4'            => 'comp4_p4',
        ];

        return $aliases[$col] ?? $col;
    }

    /**
     * Parsea una fecha en múltiples formatos.
     */
    private function parsearFecha(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Serial Excel
        if (is_numeric($raw) && $raw > 1000 && $raw < 200000) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);
                $y  = (int) $dt->format('Y');
                if ($y >= 1900 && $y <= 2099) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Throwable $e) {}
        }

        $part     = substr($raw, 0, 10);
        $formatos = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'm/d/Y'];

        foreach ($formatos as $fmt) {
            $dt = \DateTime::createFromFormat('!' . $fmt, $part);
            if ($dt !== false) {
                $y = (int) $dt->format('Y');
                if ($y >= 1900 && $y <= 2099) {
                    return $dt->format('Y-m-d');
                }
            }
        }

        $ts = strtotime($raw);
        if ($ts !== false && $ts > 0) {
            $y = (int) date('Y', $ts);
            if ($y >= 1900 && $y <= 2099) {
                return date('Y-m-d', $ts);
            }
        }

        return null;
    }

    /**
     * Genera un archivo XLSX para plantilla de calificaciones.
     */
    private function generarPlantillaXlsx(array $headers, array $rows, string $nombreBase, ?Asignacion $asignacion)
    {
        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Calificaciones');

        $lastColIdx = count($headers);
        $lastCol    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIdx);

        // Fila de título
        $titulo = 'Plantilla de Calificaciones Académicas';
        if ($asignacion) {
            $titulo .= ' — ' . ($asignacion->asignatura->nombre ?? '') .
                       ' · ' . ($asignacion->grupo->grado->nombre ?? '') .
                       ' ' . ($asignacion->grupo->seccion->nombre ?? '');
        }
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $titulo);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Fila de cabecera
        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => false],
        ];
        $sheet->fromArray([$headers], null, 'A2');
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($hdrStyle);

        // Columnas de referencia (nombres/apellidos) en gris
        $refStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f3f4f6']],
            'font' => ['color' => ['rgb' => '6b7280']],
        ];
        $lastRowRef = count($rows) + 2;
        if ($lastRowRef >= 2) {
            $sheet->getStyle("C2:D{$lastRowRef}")->applyFromArray($refStyle);
        }

        // Agrupar períodos visualmente con bandas de color
        $periodoColors = ['e0f2fe', 'dcfce7', 'fef9c3', 'fce7f3'];
        foreach ([0,1,2,3] as $pIdx) {
            $startCol = 5 + $pIdx * 4;
            $endCol   = $startCol + 3;
            $startL   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol);
            $endL     = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endCol);
            if ($lastRowRef >= 2) {
                $sheet->getStyle("{$startL}2:{$endL}2")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($periodoColors[$pIdx]);
                $sheet->getStyle("{$startL}2:{$endL}2")->getFont()->setColor(
                    (new \PhpOffice\PhpSpreadsheet\Style\Color('1e293b'))
                );
            }
        }

        // Datos
        $sheet->fromArray($rows, null, 'A3');

        // Anchos
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(16);
        for ($ci = 5; $ci <= $lastColIdx; $ci++) {
            $sheet->getColumnDimensionByColumn($ci)->setWidth(10);
        }

        $sheet->freezePane('E3');

        // Nota al pie
        $noteRow = count($rows) + 4;
        $sheet->setCellValue("A{$noteRow}", 'NOTAS:');
        $sheet->setCellValue("B{$noteRow}",
            'Valores entre 0 y 100. ' .
            'Columnas nombres/apellidos son referencia — no se importan. ' .
            'Se identifica al estudiante por numero_matricula o cedula.'
        );
        $sheet->mergeCells("B{$noteRow}:{$lastCol}{$noteRow}");
        $sheet->getStyle("A{$noteRow}")->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle("B{$noteRow}")->getFont()->setItalic(true)->setSize(9);

        $writer  = new Xlsx($ss);
        $tmpFile = tempnam(sys_get_temp_dir(), 'cal_') . '.xlsx';
        $writer->save($tmpFile);

        return response()->download($tmpFile, $nombreBase . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
