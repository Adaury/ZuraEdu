import { ConflictException, Inject, Injectable, NotFoundException } from '@nestjs/common';
import type { EstudianteDto, EstudiantesPage } from '@zuraedu/shared';
import { AuditService, ahoraUtc, MODELO_ESTUDIANTE } from '../audit/audit.service';
import {
  camposCambiadosComoLaravel,
  cambiosAuditados,
  descripcionEdicionEstudiante,
  descripcionObserverActualizado,
  EstudianteAuditable,
} from '../audit/descripcion-cambios';
import { Database, DB } from '../db/db.module';
import type { EstudiantesQuery } from './estudiantes.query';
import { consultaPagina, consultaTotal } from './estudiantes.queries';
import type { ActualizarEstudiante } from './estudiantes.update';

export interface MetaPeticion {
  ip?: string | null;
  userAgent?: string | null;
}

interface FilaEstudiante {
  id: number | string;
  numero_matricula: string;
  cedula: string | null;
  nombres: string;
  apellidos: string;
  sexo: string;
  estado: string | null;
}

function aDto(f: FilaEstudiante): EstudianteDto {
  return {
    id: Number(f.id),
    numeroMatricula: f.numero_matricula,
    cedula: f.cedula ?? null,
    nombres: f.nombres,
    apellidos: f.apellidos,
    sexo: f.sexo as 'M' | 'F',
    estado: (f.estado ?? 'activo') as EstudianteDto['estado'],
  };
}

/** Error de clave única de MySQL (ER_DUP_ENTRY / errno 1062). */
function esDuplicado(e: unknown): boolean {
  const err = e as { code?: string; errno?: number } | null;
  return err?.code === 'ER_DUP_ENTRY' || err?.errno === 1062;
}

/**
 * Estudiantes: listado (solo lectura) y edición parcial de los campos de identidad.
 *
 * Usa la conexión `DB` (con filtro de tenant automático): si faltara el contexto de tenant la consulta
 * lanza en vez de tocar datos de otros colegios. Las consultas de lectura viven en `estudiantes.queries.ts`
 * (funciones puras, comprobables sin base de datos).
 *
 * Equivalente de Admin\EstudianteController@index / @update (Laravel): excluye borrados lógicos, ordena por
 * apellidos y nombres, y audita los mismos campos sensibles con el mismo formato.
 */
@Injectable()
export class EstudiantesService {
  constructor(
    @Inject(DB) private readonly db: Database,
    private readonly auditoria: AuditService,
  ) {}

  async listar(q: EstudiantesQuery): Promise<EstudiantesPage> {
    const { total } = await consultaTotal(this.db, q).executeTakeFirstOrThrow();
    const filas = await consultaPagina(this.db, q).execute();

    const totalN = Number(total);
    return {
      data: filas.map(aDto),
      meta: { page: q.page, perPage: q.perPage, total: totalN, lastPage: Math.max(1, Math.ceil(totalN / q.perPage)) },
    };
  }

  /**
   * Edición parcial. Todo ocurre en UNA transacción: se bloquea la fila (`for update`), se calculan los
   * cambios, se actualiza y se escribe la auditoría; si algo falla no queda nada a medias.
   *
   *  - Un estudiante de otro colegio o borrado lógicamente → 404 (no se distingue de "no existe").
   *  - Si no cambia ningún campo, no se escribe nada ni se audita (igual que Laravel).
   *  - Cédula repetida dentro del colegio → 409.
   */
  async actualizar(id: number, entrada: ActualizarEstudiante, meta: MetaPeticion): Promise<EstudianteDto> {
    try {
      return await this.db.transaction().execute(async (trx) => {
        const actual = await trx
          .selectFrom('estudiantes')
          .select(['id', 'numero_matricula', 'cedula', 'nombres', 'apellidos', 'sexo', 'estado', 'fecha_nacimiento'])
          .where('estudiantes.id', '=', id)
          .where('estudiantes.deleted_at', 'is', null)
          .forUpdate()
          .executeTakeFirst();
        if (!actual) throw new NotFoundException('Estudiante no encontrado.');

        const antes: EstudianteAuditable = {
          cedula: actual.cedula ?? null,
          nombres: actual.nombres,
          apellidos: actual.apellidos,
          fecha_nacimiento: actual.fecha_nacimiento ?? null,
          estado: actual.estado ?? null,
        };
        const despues: EstudianteAuditable = {
          cedula: entrada.cedula !== undefined ? entrada.cedula : antes.cedula,
          nombres: entrada.nombres ?? antes.nombres,
          apellidos: entrada.apellidos ?? antes.apellidos,
          fecha_nacimiento: entrada.fechaNacimiento ?? antes.fecha_nacimiento,
          estado: entrada.estado ?? antes.estado,
        };

        const cambios = cambiosAuditados(antes, despues);
        if (cambios.length === 0) return aDto(actual);

        await trx
          .updateTable('estudiantes')
          .set({
            cedula: despues.cedula,
            nombres: despues.nombres!,
            apellidos: despues.apellidos!,
            fecha_nacimiento: despues.fecha_nacimiento,
            estado: despues.estado as EstudianteDto['estado'],
            updated_at: ahoraUtc(),
          })
          .where('estudiantes.id', '=', id)
          .executeTakeFirstOrThrow();

        // Laravel escribe DOS registros por edición y en este orden: primero el que genera
        // EstudianteObserver::updated() (se dispara dentro de update()) y después el del controlador. Los observers de
        // PHP no se ejecutan en una escritura desde TypeScript, así que se reproducen a mano.
        await this.auditoria.registrar(trx, {
          accion: 'estudiante.actualizado',
          modelo: MODELO_ESTUDIANTE,
          modeloId: id,
          descripcion: descripcionObserverActualizado(despues.apellidos!, despues.nombres!, camposCambiadosComoLaravel(antes, despues)),
          ip: meta.ip,
          userAgent: meta.userAgent,
        });
        await this.auditoria.registrar(trx, {
          accion: 'estudiante.editado',
          modelo: MODELO_ESTUDIANTE,
          modeloId: id,
          descripcion: descripcionEdicionEstudiante(id, cambios),
          ip: meta.ip,
          userAgent: meta.userAgent,
        });

        return aDto({
          ...actual,
          cedula: despues.cedula,
          nombres: despues.nombres!,
          apellidos: despues.apellidos!,
          estado: despues.estado,
        });
      });
    } catch (e) {
      if (esDuplicado(e)) throw new ConflictException('Esta cédula ya está registrada en otro estudiante.');
      throw e;
    }
  }
}
