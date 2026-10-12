<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Asignacion;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\CalificacionAudit;
use App\Models\ImportacionCalificacion;
use App\Models\Notificacion;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Traits\NormalizesFileEncoding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Recomendación 6 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md (#9/#10): procesa en
 * cola la carga masiva de calificaciones, en vez de bloquear la petición HTTP.
 * Unifica la lógica de fila que antes estaba duplicada entre
 * CalificacionController::importStore() (soportaba academica + técnica/simple,
 * sin recalcularPromedios()) e ImportacionController::calificacionesImportar()
 * (solo academica, con recalcularPromedios() y resultado por fila) — ambos
 * controladores ahora despachan este mismo Job en vez de procesar inline.
 */
class ImportarCalificacionesJob extends TenantJob
{
    use NormalizesFileEncoding;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(public readonly int $importacionId)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $importacion = ImportacionCalificacion::find($this->importacionId);
        if (! $importacion) {
            Log::warning("ImportarCalificacionesJob: importación {$this->importacionId} no encontrada.");
            return;
        }

        $importacion->update(['estado' => 'procesando', 'iniciado_at' => now()]);

        try {
            $asignacion = Asignacion::with(['asignatura', 'grupo'])->find($importacion->asignacion_id);
            if (! $asignacion) {
                throw new \RuntimeException('La asignación de este lote ya no existe.');
            }

            $rows = $this->leerFilas($importacion->archivo_path);

            $schoolYear      = SchoolYear::actual();
            $periodosIndexed = Periodo::where('school_year_id', $asignacion->school_year_id)
                ->orderBy('numero')->get()->keyBy('numero');
            $periodoFijo     = $importacion->periodo_id ? Periodo::find($importacion->periodo_id) : null;

            $matriculasPorNum    = $asignacion->grupo->matriculas()
                ->activas()->with('estudiante')->get()->keyBy(fn ($m) => $m->estudiante->numero_matricula ?? '');
            $matriculasPorCedula = $matriculasPorNum->groupBy(fn ($m) => $m->estudiante->cedula ?? '');
            // Respaldo cuando el Excel del docente solo trae nombres: por conjunto de palabras
            // ("Pérez Juan" = "Juan Pérez"), y solo si el nombre es único en el grupo.
            $clave = fn (string $t) => collect(explode(' ', \App\Support\EncabezadosImportacion::normalizar($t)))
                ->filter()->sort()->implode(' ');
            $matriculasPorNombre = $matriculasPorNum->groupBy(
                fn ($m) => $clave(($m->estudiante->nombres ?? '') . ' ' . ($m->estudiante->apellidos ?? ''))
            );

            $esAcademica = $asignacion->area === 'academica';
            $importados  = 0;
            $omitidos    = 0;
            $errores     = [];

            foreach ($rows as $i => $row) {
                $linea  = $row['__linea'] ?? ($i + 2);
                $numMat = trim($row['numero_matricula'] ?? $row['num_matricula'] ?? '');
                $cedula = trim($row['cedula'] ?? '');
                $nombreArchivo = trim(
                    ($row['nombre_completo'] ?? '') !== ''
                        ? $row['nombre_completo']
                        : trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? ''))
                );

                // Fila sin ningún dato que identifique al estudiante (totales, separadores): se ignora.
                if ($numMat === '' && $cedula === '' && $nombreArchivo === '') {
                    continue;
                }

                $matricula = null;
                if ($numMat && $matriculasPorNum->has($numMat)) {
                    $matricula = $matriculasPorNum->get($numMat);
                } elseif ($cedula && $matriculasPorCedula->has($cedula)) {
                    $matricula = $matriculasPorCedula->get($cedula)->first();
                } elseif ($nombreArchivo !== '') {
                    $candidatas = $matriculasPorNombre->get($clave($nombreArchivo), collect());
                    if ($candidatas->count() > 1) {
                        $errores[] = "Fila {$linea}: el nombre '{$nombreArchivo}' coincide con {$candidatas->count()} estudiantes del grupo; usa la matrícula o la cédula.";
                        $omitidos++;
                        continue;
                    }
                    $matricula = $candidatas->first();
                }

                if (! $matricula) {
                    $errores[] = "Fila {$linea}: estudiante no encontrado (matrícula: '{$numMat}', cédula: '{$cedula}'"
                        . ($nombreArchivo !== '' ? ", nombre: '{$nombreArchivo}'" : '') . ').';
                    $omitidos++;
                    continue;
                }

                $nombre = trim(($matricula->estudiante->apellidos ?? '') . ', ' . ($matricula->estudiante->nombres ?? ''));

                if ($esAcademica) {
                    [$ok, $err] = $this->procesarFilaAcademica($row, $matricula, $asignacion, $schoolYear, $nombre, $importacion->user_id);
                } else {
                    [$ok, $err] = $this->procesarFilaTecnicaSimple($row, $matricula, $asignacion, $periodosIndexed, $periodoFijo, $nombre, $importacion->user_id);
                }

                if ($err) $errores[] = "Fila {$linea}: {$err}";
                $ok ? $importados++ : $omitidos++;
            }

            $importacion->update([
                'estado'        => 'completado',
                'total_filas'   => count($rows),
                'importados'    => $importados,
                'omitidos'      => $omitidos,
                'errores'       => $errores,
                'completado_at' => now(),
            ]);

            $this->notificar($importacion, "Importación completada: {$importados} nota(s) importada(s)" . ($omitidos ? ", {$omitidos} omitida(s)." : '.'));
        } catch (\Throwable $e) {
            Log::error("ImportarCalificacionesJob falló para importación {$this->importacionId}: {$e->getMessage()}");
            $importacion->update([
                'estado'        => 'fallido',
                'error_fatal'   => $e->getMessage(),
                'completado_at' => now(),
            ]);
            $this->notificar($importacion, 'Tu importación de calificaciones falló: ' . Str::limit($e->getMessage(), 150));
        } finally {
            Storage::disk('local')->delete($importacion->archivo_path);
        }
    }

    /** @return array{0: bool, 1: ?string} [importado_ok, mensaje_error] */
    private function procesarFilaAcademica(array $row, $matricula, Asignacion $asignacion, ?SchoolYear $schoolYear, string $nombre, ?int $modificadoPor): array
    {
        $data       = [];
        $tieneDatos = false;
        $invalidos  = [];

        foreach (['p1', 'p2', 'p3', 'p4'] as $pIdx => $pKey) {
            $pNum = $pIdx + 1;
            foreach ([1, 2, 3, 4] as $c) {
                $parsed = $this->parsearNota((string) ($row["comp{$c}_{$pKey}"] ?? $row["{$pKey}_comp{$c}"] ?? ''));
                if ($parsed === null) continue;

                if (! is_float($parsed)) {
                    $invalidos[] = "comp{$c}_p{$pNum}='{$parsed}' no numérico";
                    continue;
                }

                $nota = $parsed;
                if ($nota < 0 || $nota > 100) {
                    $invalidos[] = "comp{$c}_p{$pNum}={$nota} fuera de rango [0-100]";
                    continue;
                }

                $data["comp{$c}_p{$pNum}"] = $nota;
                $tieneDatos = true;
            }
        }

        if (! $tieneDatos) {
            $msg = "{$nombre}: sin datos numéricos válidos" . ($invalidos ? ' (' . implode('; ', $invalidos) . ')' : '') . ' — omitida.';
            return [false, $msg];
        }

        $anterior = CalificacionAcademica::where(
            ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $schoolYear?->id]
        )->first();
        $situacionAnterior = $anterior?->situacion;

        $calAcad = CalificacionAcademica::updateOrCreate(
            ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $schoolYear?->id],
            array_merge($data, ['modificado_por' => $modificadoPor])
        );
        $calAcad->recalcularPromedios();
        $calAcad->refresh();

        // Auditoría: este Job procesa la importación masiva de Admin/Coordinación
        // (ver [[project_auditoria_boletin_completiva_gap_2026_09_13]]). No hay
        // auth()/request() en un worker de cola, así que se audita con el
        // usuario explícito ($modificadoPor) en vez de CalificacionAudit::
        // registrarCambios()/ActivityLog::registrar() (que asumen auth()->id()).
        $this->auditarCambios('CalificacionAcademica', $anterior, $data, $matricula->id, $asignacion->id, array_keys($data), $modificadoPor);
        if ($situacionAnterior !== $calAcad->situacion) {
            ActivityLog::create([
                'user_id'     => $modificadoPor,
                'accion'      => 'boletin.situacion_cambiada',
                'modelo'      => CalificacionAcademica::class,
                'modelo_id'   => $calAcad->id,
                'descripcion' => "Matrícula #{$matricula->id} | Asignación: {$asignacion->asignatura->nombre} | Situación: "
                    . ($situacionAnterior ?? '—') . ' → ' . ($calAcad->situacion ?? '—') . ' (importación masiva)',
            ]);
        }

        return $invalidos ? [true, "{$nombre}: importado con advertencias (" . implode('; ', $invalidos) . ').'] : [true, null];
    }

    /**
     * Igual que CalificacionAudit::registrarCambios(), pero con user_id
     * explícito -- en un worker de cola no hay auth()->id() ni request()->ip().
     */
    private function auditarCambios(string $modelo, $anterior, array $nuevosDatos, int $matriculaId, int $asignacionId, array $camposVigilar, ?int $userId): void
    {
        foreach ($camposVigilar as $campo) {
            $valNuevo    = isset($nuevosDatos[$campo]) && $nuevosDatos[$campo] !== '' ? (float) $nuevosDatos[$campo] : null;
            $valAnterior = $anterior ? ($anterior->$campo !== null ? (float) $anterior->$campo : null) : null;
            if ($valAnterior === $valNuevo) continue;

            CalificacionAudit::create([
                'modelo'         => $modelo,
                'registro_id'    => $anterior?->id ?? 0,
                'matricula_id'   => $matriculaId,
                'asignacion_id'  => $asignacionId,
                'campo'          => $campo,
                'valor_anterior' => $valAnterior,
                'valor_nuevo'    => $valNuevo,
                'user_id'        => $userId,
                'ip'             => null,
            ]);
        }
    }

    /** @return array{0: bool, 1: ?string} [importado_ok, mensaje_error] */
    private function procesarFilaTecnicaSimple(array $row, $matricula, Asignacion $asignacion, $periodosIndexed, ?Periodo $periodoFijo, string $nombre, ?int $modificadoPor): array
    {
        $periodoNum = (int) trim($row['periodo'] ?? '') ?: 1;
        $periodo    = $periodoFijo ?? $periodosIndexed->get($periodoNum);

        if (! $periodo) {
            return [false, "{$nombre}: período {$periodoNum} no encontrado."];
        }

        $parsed    = $this->parsearNota((string) ($row['nota_final'] ?? ''));
        $notaFinal = is_float($parsed) ? (string) $parsed : trim((string) ($row['nota_final'] ?? ''));
        if (! is_float($parsed)) {
            return [false, "{$nombre}: nota_final '{$notaFinal}' no es válida."];
        }

        $anterior = Calificacion::where(
            ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id]
        )->first();

        Calificacion::updateOrCreate(
            ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id],
            ['nota_final' => min(100, max(0, (float) $notaFinal))]
        );

        $this->auditarCambios(
            'Calificacion', $anterior, ['nota_final' => min(100, max(0, (float) $notaFinal))],
            $matricula->id, $asignacion->id, ['nota_final'], $modificadoPor
        );

        return [true, null];
    }

    /**
     * Lee el archivo temporal (CSV/XLSX) y devuelve filas asociativas con claves canónicas
     * (ver EncabezadosImportacion). Tolera el Excel propio del docente: título del instrumento en las
     * primeras filas, encabezados con otros nombres ("Matrícula", "C1 P1"…) y varias hojas (se usa la
     * primera que tenga encabezados reconocibles). Cada fila lleva '__linea' (número de fila real).
     *
     * @throws \RuntimeException si no se reconoce ninguna fila de encabezados
     */
    private function leerFilas(string $path): array
    {
        $fullPath = Storage::disk('local')->path($path);
        $ext      = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        /** @var array<int,array<int,array<int,mixed>>> $hojas  lista de matrices (una por hoja) */
        $hojas = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            foreach ($spreadsheet->getAllSheets() as $hoja) {
                $hojas[] = $hoja->toArray(null, true, true, false);
            }
        } else {
            $raw   = $this->normalizeToUtf8(file_get_contents($fullPath));
            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF")));
            $primera = collect($lines)->first(fn ($l) => trim($l) !== '') ?? '';
            $delim = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
            $hojas[] = array_map(fn ($l) => trim($l) === '' ? [] : str_getcsv($l, $delim), $lines);
        }

        foreach ($hojas as $matriz) {
            $detectado = \App\Support\EncabezadosImportacion::detectar($matriz);
            if (! $detectado) {
                continue;
            }

            [$filaEnc, $mapa] = $detectado;
            $rows = [];
            foreach (array_slice($matriz, $filaEnc + 1, null, true) as $idx => $celdas) {
                if (! is_array($celdas)) {
                    continue;
                }
                $fila = ['__linea' => $idx + 1];
                $vacia = true;
                foreach ($mapa as $col => $clave) {
                    if ($clave === '') {
                        continue;
                    }
                    $v = $celdas[$col] ?? '';
                    $v = is_scalar($v) ? trim((string) $v) : '';
                    if ($v !== '') {
                        $vacia = false;
                    }
                    $fila[$clave] = $v;
                }
                if (! $vacia) {
                    $rows[] = $fila;
                }
            }

            return $rows;
        }

        // Nada reconocible: decir qué encabezados se vieron para que el docente pueda corregirlo.
        $vistos = [];
        foreach (array_slice($hojas[0] ?? [], 0, 10) as $fila) {
            foreach ((array) $fila as $celda) {
                if (is_scalar($celda) && trim((string) $celda) !== '' && ! is_numeric($celda)) {
                    $vistos[] = trim((string) $celda);
                }
            }
        }
        $vistos = array_slice(array_values(array_unique($vistos)), 0, 12);

        throw new \RuntimeException(
            'No se encontró una fila de encabezados reconocible. Se necesita una columna que identifique al estudiante '
            . '(matrícula, cédula o nombre) y al menos una de notas (comp1_p1…comp4_p4, "C1 P1", "Nota final"). '
            . ($vistos ? 'Textos encontrados: ' . implode(' | ', $vistos) . '.' : 'El archivo parece vacío.')
        );
    }

    /**
     * Número de una celda de nota: acepta coma decimal ("85,5"), "%" final y, si el centro activó la
     * escala literal, letras (A+, B…). null = vacío; string = texto no interpretable (se informa).
     *
     * @return float|string|null
     */
    private function parsearNota(string $valor)
    {
        $v = trim($valor);
        if ($v === '') {
            return null;
        }

        $v = rtrim($v, '% ');
        if (preg_match('/^\d+,\d+$/', $v)) {
            $v = str_replace(',', '.', $v);
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        if (\App\Support\EscalaLiteral::activa() && ($n = \App\Support\EscalaLiteral::aNumero($v)) !== null) {
            return $n;
        }

        return $valor;
    }

    private function notificar(ImportacionCalificacion $importacion, string $mensaje): void
    {
        try {
            Notificacion::enviar(
                $importacion->user_id,
                'general',
                'Importación de calificaciones',
                $mensaje,
                ['importacion_calificacion_id' => $importacion->id]
            );
        } catch (\Throwable) {
            // No bloquear el Job por un fallo de notificación.
        }
    }
}
