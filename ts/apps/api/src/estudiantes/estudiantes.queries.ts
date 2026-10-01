import { sql } from 'kysely';
import type { Database } from '../db/db.module';
import { escapeLike, EstudiantesQuery } from './estudiantes.query';

/**
 * Índice creado por la migración de Laravel 2026_09_30_000003_indice_listado_estudiantes:
 * (tenant_id, deleted_at, apellidos, nombres). Cubre el filtro Y el orden del listado, y también
 * "cubre" el conteo (index-only).
 */
export const ESTUDIANTES_LISTADO_IDX = 'est_tenant_listado_idx';

/**
 * Filtros comunes del listado y del conteo. No hay ningún `where tenant_id` escrito aquí a propósito:
 * lo agrega TenantScopePlugin (y lanza si faltara el contexto de tenant).
 */
export function consultaBase(db: Database, q: EstudiantesQuery) {
  return db
    .selectFrom('estudiantes')
    .where('estudiantes.deleted_at', 'is', null)
    .$if(!!q.estado, (qb) => qb.where('estudiantes.estado', '=', q.estado!))
    .$if(!!q.q, (qb) => {
      const like = `%${escapeLike(q.q!)}%`;
      return qb.where((eb) =>
        eb.or([
          eb('estudiantes.nombres', 'like', like),
          eb('estudiantes.apellidos', 'like', like),
          eb('estudiantes.numero_matricula', 'like', like),
          eb('estudiantes.cedula', 'like', like),
        ]),
      );
    });
}

/**
 * Total para la paginación.
 *
 * Sin filtros usa un *hint del optimizador* de MySQL que obliga a recorrer solo el índice del listado
 * (index-only): 11,7 ms -> 1,6 ms con 4.950 filas, total exacto. Es necesario porque el optimizador
 * estima mal las filas del índice único (tenant_id, cedula) (2.393 estimadas vs 4.950 reales) y lo
 * prefiere, leyendo cada fila para comprobar deleted_at.
 *
 * Por qué un hint y no `FORCE INDEX`: si el índice no existe en algún entorno (migración sin aplicar)
 * el hint se IGNORA sin error, mientras que FORCE INDEX daría el error 1176. Además va junto al
 * SELECT y no toca el nombre de la tabla, así que TenantScopePlugin sigue reconociéndola.
 * El nombre del índice es una constante nuestra, nunca viene del cliente.
 *
 * Con filtros (búsqueda por texto o estado) no se usa: el índice no los cubre y el optimizador decide.
 */
export function consultaTotal(db: Database, q: EstudiantesQuery) {
  const sinFiltros = !q.q && !q.estado;
  return consultaBase(db, q)
    .$if(sinFiltros, (qb) => qb.modifyFront(sql.raw(`/*+ INDEX(estudiantes ${ESTUDIANTES_LISTADO_IDX}) */`)))
    .select((eb) => eb.fn.countAll<number>().as('total'));
}

export function consultaPagina(db: Database, q: EstudiantesQuery) {
  return consultaBase(db, q)
    .select([
      'estudiantes.id',
      'estudiantes.numero_matricula',
      'estudiantes.cedula',
      'estudiantes.nombres',
      'estudiantes.apellidos',
      'estudiantes.sexo',
      'estudiantes.estado',
    ])
    .orderBy('estudiantes.apellidos')
    .orderBy('estudiantes.nombres')
    .orderBy('estudiantes.id')
    .limit(q.perPage)
    .offset((q.page - 1) * q.perPage);
}
