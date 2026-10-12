<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Support\EncabezadosImportacion;
use App\Support\EscalaLiteral;
use App\Traits\NormalizesFileEncoding;
use Illuminate\Support\Collection;

/**
 * Lectura y emparejamiento de un archivo de calificaciones (CSV/XLSX/XLS). Lo usan la vista previa
 * (antes de importar) y ImportarCalificacionesJob (al importar), para que ambos vean exactamente lo mismo.
 */
class LectorCalificaciones
{
    use NormalizesFileEncoding;

    /**
     * Lee el archivo y devuelve las filas con claves canónicas (ver EncabezadosImportacion), la hoja y
     * la fila de encabezados usadas y los encabezados originales ↔ claves reconocidas.
     * Cada fila lleva '__linea' (número de fila real en el archivo).
     *
     * @return array{filas: array<int,array<string,string|int>>, columnas: array<int,array{original:string,clave:string}>, hoja: ?string, fila_encabezado: int}
     * @throws \RuntimeException si no se reconoce ninguna fila de encabezados
     */
    public function leer(string $fullPath): array
    {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        /** @var array<int,array{titulo:?string,matriz:array}> $hojas */
        $hojas = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            foreach ($spreadsheet->getAllSheets() as $hoja) {
                $hojas[] = ['titulo' => $hoja->getTitle(), 'matriz' => $hoja->toArray(null, true, true, false)];
            }
        } else {
            $raw     = $this->normalizeToUtf8(file_get_contents($fullPath));
            $lines   = explode("\n", str_replace(["\r\n", "\r"], "\n", ltrim($raw, "\xEF\xBB\xBF")));
            $primera = collect($lines)->first(fn ($l) => trim($l) !== '') ?? '';
            $delim   = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
            $hojas[] = [
                'titulo' => null,
                'matriz' => array_map(fn ($l) => trim($l) === '' ? [] : str_getcsv($l, $delim), $lines),
            ];
        }

        foreach ($hojas as $hoja) {
            $detectado = EncabezadosImportacion::detectar($hoja['matriz']);
            if (! $detectado) {
                continue;
            }

            [$filaEnc, $mapa] = $detectado;

            $columnas = [];
            foreach ($mapa as $col => $clave) {
                if ($clave === '') {
                    continue;
                }
                $columnas[] = [
                    'original' => trim((string) ($hoja['matriz'][$filaEnc][$col] ?? '')),
                    'clave'    => str_contains($clave, '__dup') ? '' : $clave,   // repetida: no se usa
                ];
            }

            $rows = [];
            foreach (array_slice($hoja['matriz'], $filaEnc + 1, null, true) as $idx => $celdas) {
                if (! is_array($celdas)) {
                    continue;
                }
                $fila  = ['__linea' => $idx + 1];
                $vacia = true;
                foreach ($mapa as $col => $clave) {
                    if ($clave === '' || str_contains($clave, '__dup')) {
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

            return ['filas' => $rows, 'columnas' => $columnas, 'hoja' => $hoja['titulo'], 'fila_encabezado' => $filaEnc + 1];
        }

        // Nada reconocible: decir qué encabezados se vieron para que el docente pueda corregirlo.
        $vistos = [];
        foreach (array_slice($hojas[0]['matriz'] ?? [], 0, 10) as $fila) {
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
    public function parsearNota(string $valor)
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

        if (EscalaLiteral::activa() && ($n = EscalaLiteral::aNumero($v)) !== null) {
            return $n;
        }

        return $valor;
    }

    /**
     * Índices para emparejar filas del archivo con las matrículas ACTIVAS del grupo de la asignación.
     *
     * @return array{porMatricula: Collection, porCedula: Collection, porNombre: Collection}
     */
    public function indices(Asignacion $asignacion): array
    {
        $porNum = $asignacion->grupo->matriculas()->activas()->with('estudiante')
            ->get()->keyBy(fn ($m) => $m->estudiante->numero_matricula ?? '');

        return [
            'porMatricula' => $porNum,
            'porCedula'    => $porNum->groupBy(fn ($m) => $m->estudiante->cedula ?? ''),
            // Por conjunto de palabras ("Pérez Juan" = "Juan Pérez"), sin acentos.
            'porNombre'    => $porNum->groupBy(
                fn ($m) => $this->claveNombre(($m->estudiante->nombres ?? '') . ' ' . ($m->estudiante->apellidos ?? ''))
            ),
        ];
    }

    public function claveNombre(string $texto): string
    {
        return collect(explode(' ', EncabezadosImportacion::normalizar($texto)))->filter()->sort()->implode(' ');
    }

    /**
     * Empareja una fila del archivo con una matrícula del grupo.
     *
     * @return array{0: mixed, 1: ?string, 2: string} [matrícula|null, mensaje de error|null, método]
     *         método: 'matricula' | 'cedula' | 'nombre' | 'ignorada' | 'ninguno' | 'ambiguo'
     */
    public function emparejar(array $row, array $indices): array
    {
        $linea  = $row['__linea'] ?? '?';
        $numMat = trim($row['numero_matricula'] ?? $row['num_matricula'] ?? '');
        $cedula = trim($row['cedula'] ?? '');
        $nombreArchivo = trim(
            ($row['nombre_completo'] ?? '') !== ''
                ? $row['nombre_completo']
                : trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? ''))
        );

        // Fila sin ningún dato que identifique al estudiante (totales, separadores): se ignora.
        if ($numMat === '' && $cedula === '' && $nombreArchivo === '') {
            return [null, null, 'ignorada'];
        }

        if ($numMat !== '' && $indices['porMatricula']->has($numMat)) {
            return [$indices['porMatricula']->get($numMat), null, 'matricula'];
        }
        if ($cedula !== '' && $indices['porCedula']->has($cedula)) {
            return [$indices['porCedula']->get($cedula)->first(), null, 'cedula'];
        }
        if ($nombreArchivo !== '') {
            $candidatas = $indices['porNombre']->get($this->claveNombre($nombreArchivo), collect());
            if ($candidatas->count() > 1) {
                return [null, "Fila {$linea}: el nombre '{$nombreArchivo}' coincide con {$candidatas->count()} estudiantes del grupo; usa la matrícula o la cédula.", 'ambiguo'];
            }
            if ($candidatas->count() === 1) {
                return [$candidatas->first(), null, 'nombre'];
            }
        }

        return [null, "Fila {$linea}: estudiante no encontrado (matrícula: '{$numMat}', cédula: '{$cedula}'"
            . ($nombreArchivo !== '' ? ", nombre: '{$nombreArchivo}'" : '') . ').', 'ninguno'];
    }
}
