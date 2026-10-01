<?php

namespace App\Support\Validation;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Verificador de presencia (regla `exists:`) que respeta el COLEGIO y el borrado lógico.
 *
 * El `DatabasePresenceVerifier` de Laravel consulta la tabla cruda, sin los scopes globales de Eloquent. Con
 * `exists:estudiantes,id` pasaba un estudiante de OTRO colegio y uno borrado lógicamente, y el controlador acababa
 * guardando una referencia ajena (p. ej. matricular a un estudiante de otro colegio). No se puede confiar en un id que
 * llega del navegador: aquí la existencia se comprueba dentro del colegio actual.
 *
 *  - Si hay colegio en contexto (`tenant()`, la misma condición que el scope de `BelongsToTenant`) y la tabla tiene
 *    `tenant_id`, solo cuentan las filas de ese colegio.
 *  - Si la tabla tiene `deleted_at`, las filas borradas lógicamente no "existen".
 *  - Sin colegio en contexto (superadmin, comandos, jobs) no cambia nada.
 *
 * Solo se usa para `exists`. La regla `unique` conserva el comportamiento de siempre a propósito: la restricción única de
 * la BD SÍ cuenta las filas borradas lógicamente, y cambiarla es otra decisión (ver TenantAwareValidator).
 */
class TenantPresenceVerifier extends DatabasePresenceVerifier
{
    /** @var array<string, array<string, bool>> tabla → columna → existe (en memoria: el esquema no cambia en un proceso) */
    private static array $columnas = [];

    protected function table($table)
    {
        $query = parent::table($table);

        if (tenant() !== null && $this->tieneColumna($table, 'tenant_id')) {
            $query->where("{$table}.tenant_id", tenant()->id);
        }
        if ($this->tieneColumna($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        return $query;
    }

    private function tieneColumna(string $tabla, string $columna): bool
    {
        // `exists:otra_conexion.tabla,col` llega aquí ya sin el prefijo de conexión (parseTable lo separa).
        return self::$columnas[$tabla][$columna] ??= Schema::hasColumn($tabla, $columna);
    }
}
