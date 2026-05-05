<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\QueryException;
use App\Http\Requests\Admin\StoreDocenteRequest;
use App\Http\Requests\Admin\UpdateDocenteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DocenteController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $buscar = $request->input('buscar');

        $docentes = Docente::when($buscar, function ($q, $buscar) {
                $q->where(function ($q) use ($buscar) {
                    $q->where('nombres',   'like', "%{$buscar}%")
                      ->orWhere('apellidos', 'like', "%{$buscar}%")
                      ->orWhere('cedula',    'like', "%{$buscar}%");
                });
            })
            ->orderBy('apellidos')
            ->paginate(15)
            ->withQueryString();

        return view('admin.docentes.index', compact('docentes', 'buscar'));
    }

    // ── Create ─────────────────────────────────────────────────────────────
    public function create()
    {
        return view('admin.docentes.create');
    }

    // ── Store ──────────────────────────────────────────────────────────────
    public function store(StoreDocenteRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->procesarFoto($request->file('foto'), 'fotos/docentes');
        }

        // Auto-create or link user account if email provided
        $tempPassword = null;
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                // Use cedula as default password, or generate one
                $tempPassword = Str::random(12);
                $user = User::create([
                    'name'     => trim($data['nombres'] . ' ' . $data['apellidos']),
                    'email'    => $data['email'],
                    'password' => Hash::make($tempPassword),
                    'activo'   => true,
                ]);
                $user->assignRole('Docente');
            }
            $data['user_id'] = $user->id;
        }

        try {
            Docente::create($data);
        } catch (QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                return back()->withInput()
                    ->withErrors(['cedula' => 'La cédula ingresada ya está registrada en el sistema.']);
            }
            throw $e;
        }

        $msg = 'Docente registrado correctamente.';
        if ($tempPassword) {
            $msg .= " Se creó su cuenta de acceso — contraseña temporal: {$tempPassword}";
        }

        return redirect()->route('admin.docentes.index')
                         ->with('success', $msg);
    }

    // ── Show ───────────────────────────────────────────────────────────────
    public function show(Docente $docente)
    {
        $docente->load(['asignaciones.asignatura', 'asignaciones.grupo.grado', 'asignaciones.grupo.seccion']);

        return view('admin.docentes.show', compact('docente'));
    }

    // ── Edit ───────────────────────────────────────────────────────────────
    public function edit(Docente $docente)
    {
        return view('admin.docentes.edit', compact('docente'));
    }

    // ── Update ─────────────────────────────────────────────────────────────
    public function update(UpdateDocenteRequest $request, Docente $docente)
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            if ($docente->foto) {
                Storage::disk('public')->delete($docente->foto);
            }
            $data['foto'] = $this->procesarFoto($request->file('foto'), 'fotos/docentes');
        }

        // Link user if still missing and email is available
        $tempPassword = null;
        if (empty($docente->user_id) && !empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                $tempPassword = Str::random(12);
                $user = User::create([
                    'name'     => trim($data['nombres'] . ' ' . $data['apellidos']),
                    'email'    => $data['email'],
                    'password' => Hash::make($tempPassword),
                    'activo'   => true,
                ]);
                $user->assignRole('Docente');
            }
            $data['user_id'] = $user->id;
        }

        try {
            $docente->update($data);
        } catch (QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                return back()->withInput()
                    ->withErrors(['cedula' => 'La cédula ingresada ya está registrada para otro docente.']);
            }
            throw $e;
        }

        $msg = 'Docente actualizado correctamente.';
        if ($tempPassword) {
            $msg .= " Se creó su cuenta de acceso — contraseña temporal: {$tempPassword}";
        }

        return redirect()->route('admin.docentes.index')
                         ->with('success', $msg);
    }

    // ── Destroy ────────────────────────────────────────────────────────────
    public function destroy(Docente $docente)
    {
        if ($docente->foto) {
            Storage::disk('public')->delete($docente->foto);
        }

        $docente->delete();

        return redirect()->route('admin.docentes.index')
                         ->with('success', 'Docente eliminado correctamente.');
    }

    // ── Por Área ───────────────────────────────────────────────────────────
    public function porArea(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $area       = $request->input('area', 'tecnica');

        $docentes = Docente::activos()
            ->where('area', $area)
            ->orderBy('apellidos')
            ->with([
                'asignaciones' => function ($q) use ($schoolYear) {
                    if ($schoolYear) {
                        $q->where('school_year_id', $schoolYear->id)->where('activo', true);
                    }
                    $q->with(['asignatura', 'grupo.grado', 'grupo.seccion']);
                },
            ])
            ->get();

        return view('admin.docentes.por-area', compact('docentes', 'area', 'schoolYear'));
    }

    // ── Import: mostrar formulario ─────────────────────────────────────────
    public function import()
    {
        return view('admin.docentes.import');
    }

    // ── Import: previsualizar (multi-hoja Excel) ───────────────────────────
    public function importPreview(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file      = $request->file('archivo');
        $extension = strtolower($file->getClientOriginalExtension());
        $origName  = $file->getClientOriginalName();
        $path      = $file->getRealPath();

        $tempName = 'doc_import_' . uniqid() . '.' . $extension;
        $tempPath = 'import_temp/' . $tempName;
        Storage::disk('local')->put($tempPath, file_get_contents($path));

        $hojas   = [];
        $isExcel = in_array($extension, ['xlsx', 'xls']);

        if ($isExcel && class_exists(IOFactory::class)) {
            try {
                $spreadsheet = IOFactory::load(storage_path('app/' . $tempPath));
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $datos = $sheet->toArray(null, true, false, false);
                    $datos = array_values(array_filter($datos, fn($r) =>
                        count(array_filter($r, fn($v) => trim((string)$v) !== '')) > 0
                    ));
                    if (count($datos) < 2) continue;

                    $cab   = array_map(fn($c) => $this->normCol((string)$c), $datos[0]);
                    $filas = [];
                    foreach (array_slice($datos, 1) as $fila) {
                        if (count(array_filter($fila, fn($v) => trim((string)$v) !== '')) === 0) continue;
                        $d = array_combine($cab, array_pad(array_map('strval', $fila), count($cab), ''));
                        if (!trim($d['apellidos'] ?? '') && !trim($d['nombres'] ?? '')) continue;
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
            // CSV / TXT
            $raw = file_get_contents($path);
            $enc = mb_detect_encoding($raw, ['UTF-8','Windows-1252','ISO-8859-1'], true);
            if ($enc && $enc !== 'UTF-8') $raw = mb_convert_encoding($raw, 'UTF-8', $enc);
            $raw = ltrim($raw, "\xEF\xBB\xBF");

            $tmp = tempnam(sys_get_temp_dir(), 'sge_d_');
            file_put_contents($tmp, $raw);
            $h = fopen($tmp, 'r');
            $fl = rtrim((string) fgets($h), "\r\n"); rewind($h);
            $cands = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
            foreach (array_keys($cands) as $d) $cands[$d] = count(str_getcsv($fl, $d));
            arsort($cands);
            $delim = (string) array_key_first($cands);
            if ($cands[$delim] <= 1) $delim = ',';

            $header = fgetcsv($h, 0, $delim);
            if ($header) {
                $cab   = array_map(fn($c) => $this->normCol($c), $header);
                $filas = [];
                while (($row = fgetcsv($h, 0, $delim)) !== false) {
                    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
                    $d = array_combine($cab, array_pad(array_map('strval', $row), count($cab), ''));
                    if (!trim($d['apellidos'] ?? '') && !trim($d['nombres'] ?? '')) continue;
                    $filas[] = $d;
                }
                if (!empty($filas)) $hojas[] = ['nombre' => $origName, 'filas' => $filas];
            }
            fclose($h); @unlink($tmp);
        }

        if (empty($hojas)) {
            Storage::disk('local')->delete($tempPath);
            return back()->withErrors(['archivo' => 'El archivo no contiene datos válidos con Nombres y Apellidos.']);
        }

        return view('admin.docentes.import-preview', compact('hojas', 'tempPath', 'extension', 'origName'));
    }

    // ── Import: confirmar e importar ───────────────────────────────────────
    public function importConfirm(Request $request)
    {
        $tempPath  = $request->input('temp_path', '');
        $extension = $request->input('extension', 'xlsx');

        if (!Str::startsWith($tempPath, 'import_temp/') || !Storage::disk('local')->exists($tempPath)) {
            return back()->withErrors(['archivo' => 'Archivo temporal no encontrado. Sube el archivo nuevamente.']);
        }

        $fullPath = storage_path('app/' . $tempPath);
        $isExcel  = in_array($extension, ['xlsx', 'xls']);
        $hojasData = [];

        if ($isExcel && class_exists(IOFactory::class)) {
            try {
                $spreadsheet = IOFactory::load($fullPath);
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $datos = $sheet->toArray(null, true, false, false);
                    $datos = array_values(array_filter($datos, fn($r) =>
                        count(array_filter($r, fn($v) => trim((string)$v) !== '')) > 0
                    ));
                    if (count($datos) < 2) continue;
                    $cab   = array_map(fn($c) => $this->normCol((string)$c), $datos[0]);
                    $filas = [];
                    foreach (array_slice($datos, 1) as $fila) {
                        if (count(array_filter($fila, fn($v) => trim((string)$v) !== '')) === 0) continue;
                        $d = array_combine($cab, array_pad(array_map('strval', $fila), count($cab), ''));
                        if (!trim($d['apellidos'] ?? '') && !trim($d['nombres'] ?? '')) continue;
                        $filas[] = $d;
                    }
                    if (!empty($filas)) $hojasData[] = $filas;
                }
            } catch (\Exception $e) {
                Storage::disk('local')->delete($tempPath);
                return back()->withErrors(['archivo' => 'Error al leer el archivo: ' . $e->getMessage()]);
            }
        } else {
            $raw = Storage::disk('local')->get($tempPath);
            $enc = mb_detect_encoding($raw, ['UTF-8','Windows-1252','ISO-8859-1'], true);
            if ($enc && $enc !== 'UTF-8') $raw = mb_convert_encoding($raw, 'UTF-8', $enc);
            $raw = ltrim($raw, "\xEF\xBB\xBF");
            $tmp = tempnam(sys_get_temp_dir(), 'sge_dc_');
            file_put_contents($tmp, $raw);
            $h = fopen($tmp, 'r');
            $fl = rtrim((string) fgets($h), "\r\n"); rewind($h);
            $cands = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
            foreach (array_keys($cands) as $d) $cands[$d] = count(str_getcsv($fl, $d));
            arsort($cands);
            $delim = (string) array_key_first($cands);
            if ($cands[$delim] <= 1) $delim = ',';
            $header = fgetcsv($h, 0, $delim);
            if ($header) {
                $cab = array_map(fn($c) => $this->normCol($c), $header);
                $filas = [];
                while (($row = fgetcsv($h, 0, $delim)) !== false) {
                    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
                    $d = array_combine($cab, array_pad(array_map('strval', $row), count($cab), ''));
                    if (!trim($d['apellidos'] ?? '') && !trim($d['nombres'] ?? '')) continue;
                    $filas[] = $d;
                }
                if (!empty($filas)) $hojasData[] = $filas;
            }
            fclose($h); @unlink($tmp);
        }

        $importados = 0;
        $omitidos   = 0;
        $errores    = [];
        $cuentas    = []; // [nombre_completo => contraseña_temporal]

        foreach ($hojasData as $filas) {
            $numFila = 0;
            foreach ($filas as $d) {
                $numFila++;
                $nombres   = trim($d['nombres']   ?? '');
                $apellidos = trim($d['apellidos'] ?? '');

                if (!$nombres || !$apellidos) {
                    $errores[] = "Fila {$numFila}: nombres y apellidos obligatorios.";
                    $omitidos++; continue;
                }

                $email  = trim($d['email'] ?? '') ?: null;
                $cedula = trim($d['cedula'] ?? '') ?: null;

                if ($cedula && Docente::where('cedula', $cedula)->exists()) {
                    $errores[] = "Fila {$numFila}: cédula {$cedula} ya registrada — omitida.";
                    $omitidos++; continue;
                }
                if ($email && Docente::where('email', $email)->exists()) {
                    $errores[] = "Fila {$numFila}: email {$email} ya registrado — omitida.";
                    $omitidos++; continue;
                }

                try {
                    $docente = Docente::create([
                        'nombres'          => $nombres,
                        'apellidos'        => $apellidos,
                        'cedula'           => $cedula,
                        'email'            => $email,
                        'telefono'         => trim($d['telefono']         ?? '') ?: null,
                        'especialidad'     => trim($d['especialidad']     ?? '') ?: null,
                        'titulo_academico' => trim($d['titulo_academico'] ?? '') ?: null,
                        'sexo'             => in_array(strtoupper(trim($d['sexo'] ?? '')), ['M','F'])
                                             ? strtoupper(trim($d['sexo'])) : null,
                        'area'             => in_array(strtolower(trim($d['area'] ?? '')), ['tecnica','administrativa','otro'])
                                             ? strtolower(trim($d['area'])) : 'otro',
                        'cargo'            => trim($d['cargo'] ?? '') ?: null,
                        'estado'           => 'activo',
                    ]);

                    // Crear cuenta de usuario si tiene email
                    if ($email) {
                        $user = User::where('email', $email)->first();
                        if (!$user) {
                            $tempPass = Str::random(8) . rand(10, 99); // ej: xKpQmYrz47
                            $user = User::create([
                                'name'                => $nombres,
                                'apellidos'           => $apellidos,
                                'email'               => $email,
                                'password'            => Hash::make($tempPass),
                                'activo'              => true,
                                'must_change_password' => true,
                            ]);
                            $user->assignRole('Docente');
                            $cuentas["{$nombres} {$apellidos}"] = [
                                'email' => $email,
                                'pass'  => $tempPass,
                            ];
                        }
                        $docente->update(['user_id' => $user->id]);
                    }

                    $importados++;
                } catch (QueryException $e) {
                    $errores[] = "Fila {$numFila}: {$nombres} {$apellidos} — error al guardar.";
                    $omitidos++;
                }
            }
        }

        Storage::disk('local')->delete($tempPath);

        $msg = "Importación completada: {$importados} docente(s) registrado(s)";
        $conCuenta = count($cuentas);
        if ($conCuenta > 0) $msg .= ", {$conCuenta} cuenta(s) de acceso creada(s)";
        $msg .= '.';
        if ($omitidos) $msg .= " {$omitidos} fila(s) omitida(s).";

        return redirect()->route('admin.docentes.import')
            ->with('success', $msg)
            ->with('errores_import', $errores)
            ->with('cuentas_creadas', $cuentas);
    }

    // ── Descargar plantilla Excel ──────────────────────────────────────────
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Docentes');

        $obligatorias = ['Apellidos', 'Nombres', 'Email'];
        $opcionales   = ['Cédula', 'Teléfono', 'Especialidad', 'Título Académico', 'Sexo', 'Área', 'Cargo'];
        $headers      = array_merge($obligatorias, $opcionales);
        $sheet->fromArray([$headers], null, 'A1');

        $lastObCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($obligatorias));
        $firstOpCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($obligatorias) + 1);
        $lastCol    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

        $sheet->getStyle('A1:' . $lastObCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle($firstOpCol . '1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '444444']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Filas de ejemplo
        $sheet->fromArray([['González Martínez', 'María Elena', 'maria@escuela.do', '001-9876543-2', '809-555-9999', 'Informática', 'Licenciada en Informática', 'F', 'tecnica', 'Docente']], null, 'A2');
        $sheet->fromArray([['Ramírez Cruz', 'Carlos Antonio', 'carlos@escuela.do', '', '809-555-1111', 'Matemáticas', 'Licenciado en Educación', 'M', 'administrativa', 'Coordinador']], null, 'A3');

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $sheet->setCellValue('A5', 'NOTAS:');
        $sheet->setCellValue('B5', 'Solo Apellidos, Nombres y Email son obligatorios (azul). El Email crea una cuenta de acceso automáticamente con contraseña temporal. Sexo = M o F. Área = tecnica / administrativa / otro.');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('B5')->getFont()->setItalic(true)->setSize(9);
        $sheet->mergeCells('B5:' . $lastCol . '5');

        $writer  = new XlsxWriter($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'plantilla_doc_') . '.xlsx';
        $writer->save($tmpFile);

        return response()->download($tmpFile, 'plantilla_docentes.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Import: process CSV (método original, mantener compatibilidad) ─────
    public function importStore(Request $request)
    {
        // Redirigir al nuevo flujo de previsualización
        return $this->importPreview($request);
    }

    // ── Normalizar cabecera de columna ─────────────────────────────────────
    private function normCol(string $col): string
    {
        $col = mb_strtolower(trim($col), 'UTF-8');
        $col = strtr($col, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u']);
        $col = preg_replace('/[\s.]+/', '_', $col);
        $col = preg_replace('/[^\w]/', '', $col);
        $col = trim($col, '_');
        $map = [
            'apellido'          => 'apellidos',
            'nombre'            => 'nombres',
            'correo'            => 'email',
            'correo_electronico'=> 'email',
            'titulo'            => 'titulo_academico',
            'titulo_academico'  => 'titulo_academico',
            'especialidad'      => 'especialidad',
            'area'              => 'area',
            'telefono'          => 'telefono',
            'telfono'           => 'telefono',
            'cedula'            => 'cedula',
            'cdula'             => 'cedula',
            'sexo'              => 'sexo',
            'cargo'             => 'cargo',
            'apellidos'         => 'apellidos',
            'nombres'           => 'nombres',
            'email'             => 'email',
        ];
        return $map[$col] ?? $col;
    }

    // ── Private helpers ────────────────────────────────────────────────────
    /**
     * Resize & save uploaded photo, return stored path relative to storage/app/public.
     */
    private function procesarFoto($file, string $directory): string
    {
        $filename  = Str::uuid() . '.jpg';
        $storagePath = $directory . '/' . $filename;

        $img = Image::make($file)->fit(300, 300)->encode('jpg', 85);

        Storage::disk('public')->put($storagePath, (string) $img);

        return $storagePath;
    }

    // ── Docentes por área PDF ─────────────────────────────────────────────
    public function porAreaPdf(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $area       = $request->input('area', 'tecnica');

        $docentes = Docente::activos()
            ->where('area', $area)
            ->orderBy('apellidos')
            ->with([
                'asignaciones' => function ($q) use ($schoolYear) {
                    if ($schoolYear) {
                        $q->where('school_year_id', $schoolYear->id)->where('activo', true);
                    }
                    $q->with(['asignatura', 'grupo.grado', 'grupo.seccion']);
                },
            ])
            ->get();

        $areaLabel = match($area) {
            'tecnica'        => 'Técnica',
            'administrativa' => 'Administrativa',
            default          => 'Otro',
        };

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $config = $schoolYear ? \App\Models\BoletinConfig::getOrCreate($schoolYear->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.docentes.por_area_pdf',
            compact('docentes', 'area', 'areaLabel', 'schoolYear', 'inst', 'config')
        )->setPaper('letter', 'portrait');

        return $pdf->download("docentes_area_{$area}_" . now()->format('Ymd') . '.pdf');
    }

    // ── Lista de docentes Excel ────────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $sy      = SchoolYear::actual();
        $docentes = Docente::with([
            'user',
            'asignaciones' => fn($q) => $q->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                ->with('asignatura', 'grupo.grado', 'grupo.seccion'),
        ])
        ->orderBy('apellidos')
        ->get();

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Docentes');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'NÓMINA DE DOCENTES — ' . ($sy?->nombre ?? date('Y')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Apellidos', 'Nombres', 'Cédula', 'Teléfono', 'Email', 'Asignaturas Asignadas', 'Grupos', 'Estado'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '2';
            $sheet->setCellValue($cell, $h);
        }
        $sheet->getStyle('A2:I2')->applyFromArray($hdrStyle);

        foreach ($docentes as $i => $d) {
            $row      = $i + 3;
            $asigNom  = $d->asignaciones->map(fn($a) => $a->asignatura?->nombre)->unique()->filter()->implode(', ');
            $grupoNom = $d->asignaciones->map(fn($a) => $a->grupo ? ($a->grupo->grado->nombre ?? '') . ' ' . ($a->grupo->seccion->nombre ?? '') : '')->unique()->filter()->implode(', ');

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $d->apellidos ?? '');
            $sheet->setCellValue("C{$row}", $d->nombres ?? '');
            $sheet->setCellValue("D{$row}", $d->cedula ?? '');
            $sheet->setCellValue("E{$row}", $d->telefono ?? '');
            $sheet->setCellValue("F{$row}", $d->user?->email ?? '');
            $sheet->setCellValue("G{$row}", $asigNom ?: '—');
            $sheet->setCellValue("H{$row}", $grupoNom ?: '—');
            $sheet->setCellValue("I{$row}", $d->activo ? 'Activo' : 'Inactivo');

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'I') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new XlsxWriter($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'doc_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'docentes_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista general PDF ────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $sy      = SchoolYear::actual();
        $docentes = Docente::with([
            'user',
            'asignaciones' => fn($q) => $q->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                ->with('asignatura', 'grupo.grado', 'grupo.seccion'),
        ])
        ->orderBy('apellidos')
        ->get();

        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.docentes.lista_pdf',
            compact('docentes', 'inst', 'sy')
        )->setPaper('letter', 'landscape');

        return $pdf->download('docentes_' . now()->format('Ymd') . '.pdf');
    }

    public function porAreaExcel(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        $area       = $request->input('area', 'tecnica');

        $docentes = Docente::activos()
            ->where('area', $area)
            ->orderBy('apellidos')
            ->with([
                'asignaciones' => function ($q) use ($schoolYear) {
                    if ($schoolYear) {
                        $q->where('school_year_id', $schoolYear->id)->where('activo', true);
                    }
                    $q->with(['asignatura', 'grupo.grado', 'grupo.seccion']);
                },
            ])
            ->get();

        $areaLabel = match($area) {
            'tecnica'        => 'Técnica',
            'administrativa' => 'Administrativa',
            default          => 'Otro',
        };

        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle("Docentes {$areaLabel}");

        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', "Docentes Área {$areaLabel} — " . ($schoolYear?->nombre ?? ''));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Apellidos', 'Nombres', 'Cédula', 'Asignaciones'];
        foreach ($headers as $i => $h) {
            $cell = chr(65 + $i) . '3';
            $ws->setCellValue($cell, $h);
            $ws->getStyle($cell)->getFont()->setBold(true);
            $ws->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
               ->getStartColor()->setRGB('1e3a6e');
            $ws->getStyle($cell)->getFont()->getColor()->setRGB('ffffff');
        }

        foreach ($docentes as $i => $doc) {
            $row   = $i + 4;
            $asigs = $doc->asignaciones->map(fn($a) =>
                ($a->asignatura?->nombre ?? '?') . ' (' . ($a->grupo?->nombre_completo ?? '?') . ')'
            )->implode('; ');

            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $doc->apellidos ?? '—');
            $ws->setCellValue("C{$row}", $doc->nombres ?? $doc->nombre ?? '—');
            $ws->setCellValue("D{$row}", $doc->cedula ?? '—');
            $ws->setCellValue("E{$row}", $asigs ?: '—');

            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        $ws->getColumnDimension('E')->setWidth(60);
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new XlsxWriter($ss);
        $filename = "docentes_area_{$area}_" . now()->format('Ymd') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
