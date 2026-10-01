import { Inject, Injectable } from '@nestjs/common';
import type { EstudianteDto, EstudiantesPage } from '@zuraedu/shared';
import { Database, DB } from '../db/db.module';
import { escapeLike, EstudiantesQuery } from './estudiantes.query';

/**
 * Primer corte vertical de la migración (solo lectura): listado de estudiantes.
 *
 * Usa la conexión `DB` (con filtro de tenant automático): no hay ningún `where tenant_id` escrito
 * aquí a propósito — lo agrega TenantScopePlugin, y si faltara el contexto de tenant la consulta
 * lanza en vez de devolver datos de otros colegios.
 *
 * Equivalente de Admin\EstudianteController@index (Laravel): excluye borrados lógicos y ordena
 * por apellidos, nombres.
 */
@Injectable()
export class EstudiantesService {
  constructor(@Inject(DB) private readonly db: Database) {}

  async listar(q: EstudiantesQuery): Promise<EstudiantesPage> {
    const base = this.db
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

    const { total } = await base
      .select((eb) => eb.fn.countAll<number>().as('total'))
      .executeTakeFirstOrThrow();

    const filas = await base
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
      .offset((q.page - 1) * q.perPage)
      .execute();

    const totalN = Number(total);
    const data: EstudianteDto[] = filas.map((f) => ({
      id: Number(f.id),
      numeroMatricula: f.numero_matricula,
      cedula: f.cedula ?? null,
      nombres: f.nombres,
      apellidos: f.apellidos,
      sexo: f.sexo as 'M' | 'F',
      estado: (f.estado ?? 'activo') as EstudianteDto['estado'],
    }));

    return {
      data,
      meta: { page: q.page, perPage: q.perPage, total: totalN, lastPage: Math.max(1, Math.ceil(totalN / q.perPage)) },
    };
  }
}
