<?php

namespace App\Support\Validation;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Verificador para la regla `unique:` que hace que la VALIDACIÓN diga lo mismo que la restricción única de la BD.
 *
 * Problema: `unique:estudiantes,cedula` consultaba TODOS los colegios, pero la BD solo exige unicidad POR colegio
 * (`UNIQUE (tenant_id, cedula)`). Resultado: un colegio no podía registrar una cédula, una matrícula, el nombre de un año
 * escolar o la sección «A» si OTRO colegio ya la tenía, y además el mensaje revelaba que existía en otro colegio.
 *
 * Regla de decisión (se mira el esquema real, no se adivina):
 *  - La BD tiene una restricción única SOLO sobre esa columna (p. ej. `users.email`, `tenants.dominio`, `equipos.codigo`):
 *    la unicidad es GLOBAL de verdad → se deja como está (si se acotara por colegio, el INSERT daría un error 500).
 *  - Si no, y la tabla tiene `tenant_id` (la restricción es `(tenant_id, columna)` o ni siquiera existe, como `libros.isbn`
 *    o `becas.nombre`): la unicidad es POR COLEGIO → solo cuentan las filas del colegio actual.
 *  - Sin colegio en contexto (superadmin, comandos, jobs): sin cambios.
 *
 * NO excluye las filas borradas lógicamente: la restricción única de la BD también las cuenta, y `unique` debe avisar antes
 * de que el INSERT falle.
 */
class TenantUniquePresenceVerifier extends DatabasePresenceVerifier
{
    /** @var array<string, array{tenant: bool, unicos: list<list<string>>}> tabla → información del esquema (en memoria) */
    private static array $esquema = [];

    public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
    {
        if (tenant() !== null && $this->esUnicidadPorColegio((string) $collection, (string) $column) && ! array_key_exists('tenant_id', $extra)) {
            $extra['tenant_id'] = tenant()->id;
        }

        return parent::getCount($collection, $column, $value, $excludeId, $idColumn, $extra);
    }

    private function esUnicidadPorColegio(string $tabla, string $columna): bool
    {
        $info = self::$esquema[$tabla] ??= [
            'tenant' => Schema::hasColumn($tabla, 'tenant_id'),
            'unicos' => collect(Schema::getIndexes($tabla))
                ->filter(fn (array $i) => ($i['unique'] ?? false) && ! ($i['primary'] ?? false))
                ->map(fn (array $i) => array_values($i['columns']))
                ->values()
                ->all(),
        ];

        if (! $info['tenant']) {
            return false;
        }
        foreach ($info['unicos'] as $columnas) {
            if ($columnas === [$columna]) {
                return false; // unicidad global exigida por la propia BD
            }
        }

        return true;
    }
}
