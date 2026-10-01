<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice para el listado paginado de estudiantes (hallado al medir la API TypeScript con la BD
 * sintética de carga, 4.950 estudiantes; ver docs y ts/README.md).
 *
 * El listado (Admin\EstudianteController@index y la API) ejecuta:
 *
 *   select ... from estudiantes
 *   where tenant_id = ? and deleted_at is null      -- BelongsToTenant + SoftDeletes
 *   order by apellidos, nombres
 *   limit N offset M
 *
 * Sin un índice que cubra filtro Y orden, MySQL lee todas las filas del tenant y las ordena en
 * memoria ("Using filesort") en cada página. Con (tenant_id, deleted_at, apellidos, nombres)
 * recorre el índice ya ordenado y se detiene en N filas (el id de la PK va implícito al final de
 * todo índice secundario de InnoDB):
 *
 *   página 1:    17,0 ms -> 0,4 ms
 *   página 100:  27,1 ms -> 7,8 ms
 *
 * No es redundante con los índices existentes (ninguno lo tiene como prefijo) y no reemplaza a
 * los únicos (tenant_id, cedula) / (tenant_id, numero_matricula), que protegen la integridad.
 *
 * Nota: el count(*) del paginador NO mejora con este índice (el optimizador de MySQL sigue
 * eligiendo (tenant_id, cedula) y lee las filas para comprobar deleted_at).
 */
return new class extends Migration
{
    private const INDICE = 'est_tenant_listado_idx';

    public function up(): void
    {
        if (Schema::hasTable('estudiantes') && ! Schema::hasIndex('estudiantes', self::INDICE)) {
            Schema::table('estudiantes', function (Blueprint $t) {
                $t->index(['tenant_id', 'deleted_at', 'apellidos', 'nombres'], self::INDICE);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('estudiantes') && Schema::hasIndex('estudiantes', self::INDICE)) {
            Schema::table('estudiantes', function (Blueprint $t) {
                $t->dropIndex(self::INDICE);
            });
        }
    }
};
