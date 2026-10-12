<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Reconoce los encabezados de un archivo de calificaciones aunque no sean los de nuestra plantilla:
 * los docentes llegan con SU Excel ("Matrícula", "Nombre completo", "C1 P1", "Competencia 1 – Período 2",
 * título del instrumento en las primeras filas, varias hojas…). Devuelve siempre las claves canónicas
 * que entiende ImportarCalificacionesJob: numero_matricula, cedula, nombres, apellidos, nombre_completo,
 * periodo, nota_final y comp{1-4}_p{1-4}.
 */
class EncabezadosImportacion
{
    private const SINONIMOS = [
        'numero_matricula' => [
            'numero matricula', 'num matricula', 'no matricula', 'n matricula', 'nro matricula', 'numero de matricula',
            'matricula', 'id estudiante', 'codigo estudiante', 'codigo', 'id',
        ],
        'cedula' => ['cedula', 'cedula identidad', 'no cedula', 'numero cedula', 'documento', 'identificacion', 'dni'],
        'nombres' => ['nombres', 'primer nombre'],
        'apellidos' => ['apellidos', 'apellido'],
        'nombre_completo' => [
            'nombre completo', 'nombres y apellidos', 'apellidos y nombres', 'nombre y apellido', 'nombre y apellidos',
            'apellido y nombre', 'estudiante', 'alumno', 'nombre del estudiante', 'nombre del alumno', 'nombre',
        ],
        'periodo' => ['periodo', 'per'],
        'nota_final' => ['nota final', 'calificacion final', 'promedio final', 'promedio', 'final', 'nota', 'calificacion'],
    ];

    /** Máximo de filas que se revisan buscando la fila de encabezados. */
    private const FILAS_BUSQUEDA = 20;

    /** Texto normalizado: minúsculas, sin acentos, solo letras/números separados por un espacio. */
    public static function normalizar(?string $texto): string
    {
        $t = Str::of((string) $texto)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();

        return $t;
    }

    /** Clave canónica de un encabezado, o el texto normalizado (con "_") si no se reconoce. */
    public static function canonico(?string $encabezado): string
    {
        $n = self::normalizar($encabezado);
        if ($n === '') {
            return '';
        }

        $comp = '(?:competencia|comp|c)';
        $per  = '(?:periodo|per|p)';
        $cola = '(?:\s+(?:nota|calificacion|puntos?))?';
        if (preg_match("/^{$comp}\s*([1-4])\s*{$per}\s*([1-4]){$cola}$/", $n, $m)) {
            return "comp{$m[1]}_p{$m[2]}";
        }
        if (preg_match("/^{$per}\s*([1-4])\s*{$comp}\s*([1-4]){$cola}$/", $n, $m)) {
            return "comp{$m[2]}_p{$m[1]}";
        }

        foreach (self::SINONIMOS as $canonico => $lista) {
            if (in_array($n, $lista, true)) {
                return $canonico;
            }
        }

        return str_replace(' ', '_', $n);
    }

    /**
     * Mapea una fila de encabezados. @return array<int,string> clave canónica por columna ('' si vacía).
     * Si dos columnas dan la misma clave se conserva la primera (la repetida queda con su texto normalizado).
     */
    public static function mapear(array $fila): array
    {
        $mapa = [];
        $usadas = [];
        foreach ($fila as $i => $celda) {
            $c = self::canonico(is_scalar($celda) ? (string) $celda : '');
            if ($c !== '' && isset($usadas[$c])) {
                $c = $c . '__dup' . $i;
            }
            $usadas[$c] = true;
            $mapa[$i] = $c;
        }

        // "Nombre" + "Apellido(s)": el primero son los nombres, no el nombre completo.
        if (in_array('apellidos', $mapa, true) && in_array('nombre_completo', $mapa, true)) {
            $idx = array_search('nombre_completo', $mapa, true);
            $norm = self::normalizar((string) ($fila[$idx] ?? ''));
            if (in_array($norm, ['nombre', 'nombres'], true) && ! in_array('nombres', $mapa, true)) {
                $mapa[$idx] = 'nombres';
            }
        }

        return $mapa;
    }

    /** ¿La fila parece de encabezados? Identifica al estudiante y trae al menos una columna de notas. */
    private static function esEncabezado(array $mapa): bool
    {
        $claves = array_filter($mapa, fn ($c) => $c !== '' && ! str_contains($c, '__dup'));
        $identifica = (bool) array_intersect($claves, ['numero_matricula', 'cedula', 'nombres', 'apellidos', 'nombre_completo']);
        $notas = (bool) array_filter($claves, fn ($c) => $c === 'nota_final' || preg_match('/^comp[1-4]_p[1-4]$/', $c));

        return $identifica && $notas;
    }

    /**
     * Busca la fila de encabezados en las primeras filas de una hoja.
     * @param  array<int,array<int,mixed>> $filas  matriz de celdas
     * @return array{0:int,1:array<int,string>}|null  [índice de la fila, mapa de columnas]
     */
    public static function detectar(array $filas): ?array
    {
        foreach (array_slice($filas, 0, self::FILAS_BUSQUEDA, true) as $idx => $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $mapa = self::mapear($fila);
            if (self::esEncabezado($mapa)) {
                return [(int) $idx, $mapa];
            }
        }

        return null;
    }
}
