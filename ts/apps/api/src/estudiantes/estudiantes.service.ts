import { Inject, Injectable } from '@nestjs/common';
import type { EstudianteDto, EstudiantesPage } from '@zuraedu/shared';
import { Database, DB } from '../db/db.module';
import type { EstudiantesQuery } from './estudiantes.query';
import { consultaPagina, consultaTotal } from './estudiantes.queries';

/**
 * Primer corte vertical de la migración (solo lectura): listado de estudiantes.
 *
 * Usa la conexión `DB` (con filtro de tenant automático): si faltara el contexto de tenant la consulta
 * lanza en vez de devolver datos de otros colegios. Las consultas viven en `estudiantes.queries.ts`
 * (funciones puras, comprobables sin base de datos).
 *
 * Equivalente de Admin\EstudianteController@index (Laravel): excluye borrados lógicos y ordena
 * por apellidos, nombres.
 */
@Injectable()
export class EstudiantesService {
  constructor(@Inject(DB) private readonly db: Database) {}

  async listar(q: EstudiantesQuery): Promise<EstudiantesPage> {
    const { total } = await consultaTotal(this.db, q).executeTakeFirstOrThrow();
    const filas = await consultaPagina(this.db, q).execute();

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
