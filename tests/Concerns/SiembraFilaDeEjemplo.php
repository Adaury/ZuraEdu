<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inserta UNA fila de ejemplo en la tabla de un modelo leyendo la estructura real de la base (information_schema):
 * claves foráneas → primera fila de la tabla referenciada del colegio (se crea antes si está vacía), enums → primer valor,
 * fechas → hoy, textos → «Demo …». Sirve para abrir pantallas de detalle sin escribir una fábrica por cada modelo.
 */
trait SiembraFilaDeEjemplo
{
    /** Devuelve el id de la fila creada (o null si la tabla no se pudo rellenar). */
    protected function crearFilaDeEjemplo(string $tabla, int $tenantId, int $nivel = 0, array &$enProceso = []): ?int
    {
        if ($nivel > 4 || isset($enProceso[$tabla])) {
            return null;
        }
        $enProceso[$tabla] = true;
        $db = DB::connection()->getDatabaseName();
        $fk = [];
        foreach (DB::select('select column_name c, referenced_table_name t, referenced_column_name r from information_schema.key_column_usage where table_schema = ? and table_name = ? and referenced_table_name is not null', [$db, $tabla]) as $x) {
            $fk[$x->c] = [$x->t, $x->r];
        }

        $fila = [];
        $columnas = DB::select('select column_name n, data_type t, column_type ct, is_nullable nul, column_default d, extra e from information_schema.columns where table_schema = ? and table_name = ? order by ordinal_position', [$db, $tabla]);
        foreach ($columnas as $c) {
            if (str_contains($c->e, 'auto_increment') || str_contains($c->e, 'GENERATED') || $c->n === 'deleted_at') {
                continue;
            }
            if (in_array($c->n, ['created_at', 'updated_at'], true)) {
                $fila[$c->n] = now();
                continue;
            }
            if ($c->n === 'tenant_id') {
                $fila[$c->n] = $tenantId;
                continue;
            }
            if (isset($fk[$c->n])) {
                [$tp, $cp] = $fk[$c->n];
                $q = DB::table($tp)->orderBy($cp);
                if (Schema::hasColumn($tp, 'tenant_id')) {
                    $q->where('tenant_id', $tenantId);
                }
                $id = $q->value($cp) ?? ($tp !== $tabla ? $this->crearFilaDeEjemplo($tp, $tenantId, $nivel + 1, $enProceso) : null);
                if ($id !== null) {
                    $fila[$c->n] = $id;
                    continue;
                }
                if ($c->nul === 'YES') {
                    continue;
                }
            }
            if ($c->nul === 'YES' && $c->d !== null) {
                continue;
            }
            if ($c->nul === 'YES' && ! preg_match('/char|text|date|int|decimal|enum/', $c->t)) {
                continue;
            }
            if ($c->d !== null && $c->nul === 'NO') {
                continue;
            }

            $t = $c->t;
            if (str_contains($c->ct, 'tinyint(1)')) {
                $fila[$c->n] = 1;
            } elseif (in_array($t, ['int', 'bigint', 'smallint', 'mediumint', 'tinyint'], true)) {
                $fila[$c->n] = 1;
            } elseif (in_array($t, ['decimal', 'float', 'double'], true)) {
                $fila[$c->n] = 10;
            } elseif ($t === 'date') {
                $fila[$c->n] = now()->toDateString();
            } elseif (in_array($t, ['datetime', 'timestamp'], true)) {
                $fila[$c->n] = now();
            } elseif ($t === 'time') {
                $fila[$c->n] = '08:00:00';
            } elseif ($t === 'year') {
                $fila[$c->n] = (int) date('Y');
            } elseif ($t === 'json') {
                $fila[$c->n] = '[]';
            } elseif ($t === 'enum') {
                preg_match("/enum\\('([^']*)'/", $c->ct, $m);
                $fila[$c->n] = $m[1] ?? '';
            } elseif (in_array($t, ['varchar', 'char'], true)) {
                preg_match('/\\((\\d+)\\)/', $c->ct, $m);
                $largo = (int) ($m[1] ?? 50);
                $v = match (true) {
                    (bool) preg_match('/email/', $c->n) => 'demo' . random_int(1000, 9999) . '@demo.test',
                    (bool) preg_match('/url|link/', $c->n) => 'https://demo.test',
                    default => 'Demo ' . $c->n . ' ' . random_int(100, 999),
                };
                $fila[$c->n] = mb_substr($v, 0, $largo);
            } else {
                $fila[$c->n] = 'Demo texto de ejemplo';
            }
        }

        try {
            return DB::table($tabla)->insertGetId($fila);
        } catch (\Throwable) {
            return null;
        } finally {
            unset($enProceso[$tabla]);
        }
    }
}
