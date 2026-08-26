<?php

namespace App\Jobs;

use App\Models\Asignacion;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
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

            $esAcademica = $asignacion->area === 'academica';
            $importados  = 0;
            $omitidos    = 0;
            $errores     = [];

            foreach ($rows as $i => $row) {
                $linea  = $i + 2;
                $numMat = trim($row['numero_matricula'] ?? $row['num_matricula'] ?? '');
                $cedula = trim($row['cedula'] ?? '');

                $matricula = null;
                if ($numMat && $matriculasPorNum->has($numMat)) {
                    $matricula = $matriculasPorNum->get($numMat);
                } elseif ($cedula && $matriculasPorCedula->has($cedula)) {
                    $matricula = $matriculasPorCedula->get($cedula)->first();
                }

                if (! $matricula) {
                    $errores[] = "Fila {$linea}: estudiante no encontrado (matrícula: '{$numMat}', cédula: '{$cedula}').";
                    $omitidos++;
                    continue;
                }

                $nombre = trim(($matricula->estudiante->apellidos ?? '') . ', ' . ($matricula->estudiante->nombres ?? ''));

                if ($esAcademica) {
                    [$ok, $err] = $this->procesarFilaAcademica($row, $matricula, $asignacion, $schoolYear, $nombre, $importacion->user_id);
                } else {
                    [$ok, $err] = $this->procesarFilaTecnicaSimple($row, $matricula, $asignacion, $periodosIndexed, $periodoFijo, $nombre);
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
                $val = trim($row["comp{$c}_{$pKey}"] ?? $row["{$pKey}_comp{$c}"] ?? '');
                if ($val === '') continue;

                if (! is_numeric($val)) {
                    $invalidos[] = "comp{$c}_p{$pNum}='{$val}' no numérico";
                    continue;
                }

                $nota = (float) $val;
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

        $calAcad = CalificacionAcademica::updateOrCreate(
            ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $schoolYear?->id],
            array_merge($data, ['modificado_por' => $modificadoPor])
        );
        $calAcad->recalcularPromedios();

        return $invalidos ? [true, "{$nombre}: importado con advertencias (" . implode('; ', $invalidos) . ').'] : [true, null];
    }

    /** @return array{0: bool, 1: ?string} [importado_ok, mensaje_error] */
    private function procesarFilaTecnicaSimple(array $row, $matricula, Asignacion $asignacion, $periodosIndexed, ?Periodo $periodoFijo, string $nombre): array
    {
        $periodoNum = (int) trim($row['periodo'] ?? '') ?: 1;
        $periodo    = $periodoFijo ?? $periodosIndexed->get($periodoNum);

        if (! $periodo) {
            return [false, "{$nombre}: período {$periodoNum} no encontrado."];
        }

        $notaFinal = trim($row['nota_final'] ?? '');
        if ($notaFinal === '' || ! is_numeric($notaFinal)) {
            return [false, "{$nombre}: nota_final '{$notaFinal}' no es válida."];
        }

        Calificacion::updateOrCreate(
            ['matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'periodo_id' => $periodo->id],
            ['nota_final' => min(100, max(0, (float) $notaFinal))]
        );

        return [true, null];
    }

    /** Lee el archivo temporal (CSV/XLSX) y devuelve filas asociativas por encabezado. */
    private function leerFilas(string $path): array
    {
        $fullPath = Storage::disk('local')->path($path);
        $ext      = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $rows     = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $sheet       = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $header      = array_map('strtolower', array_map('trim', $sheet[0] ?? []));
            foreach (array_slice($sheet, 1) as $r) {
                $rows[] = array_combine($header, array_pad($r, count($header), null));
            }
            return $rows;
        }

        $raw   = $this->normalizeToUtf8(file_get_contents($fullPath));
        $lines = array_values(array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF")))));
        $delim = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
        $header = array_map('strtolower', array_map('trim', str_getcsv($lines[0] ?? '', $delim)));

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') continue;
            $cols   = str_getcsv($line, $delim);
            $rows[] = array_combine($header, array_pad($cols, count($header), ''));
        }

        return $rows;
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
