import { ConflictException, Inject, Injectable, NotFoundException } from '@nestjs/common';
import type { EstudianteDto, EstudiantesPage } from '@zuraedu/shared';
import { AuditService, ahoraUtc, MODELO_ESTUDIANTE } from '../audit/audit.service';
import {
  camposCambiadosComoLaravel,
  cambiosAuditados,
  descripcionEdicionEstudiante,
  descripcionObserverActualizado,
  EstudianteAuditable,
  ValoresPorColumna,
} from '../audit/descripcion-cambios';
import { Database, DB } from '../db/db.module';
import { COLUMNAS_EDITABLES, NOMBRES_COLUMNAS_EDITABLES } from './estudiante-columnas';
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

/** Qué restricción única saltó: el mensaje de MySQL nombra el índice (est_tenant_matricula_unique / ..._cedula_unique). */
function mensajeDeDuplicado(e: unknown): string {
  const texto = String((e as { sqlMessage?: string; message?: string })?.sqlMessage ?? (e as Error)?.message ?? '');
  return texto.includes('matricula')
    ? 'Este número de matrícula ya existe en otro estudiante.'
    : 'Esta cédula ya está registrada en otro estudiante.';
}

/**
 * Estudiantes: listado (solo lectura) y edición parcial.
 *
 * Usa la conexión `DB` (con filtro de tenant automático): si faltara el contexto de tenant la consulta
 * lanza en vez de tocar datos de otros colegios. Las consultas de lectura viven en `estudiantes.queries.ts`
 * (funciones puras, comprobables sin base de datos).
 *
 * Equivalente de Admin\EstudianteController@index / @update (Laravel): excluye borrados lógicos, ordena por
 * apellidos y nombres, y audita igual que Laravel (ver `actualizar`).
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
   * Edición parcial de los campos editables (ver COLUMNAS_EDITABLES). Todo ocurre en UNA transacción: se bloquea la
   * fila (`for update`), se calculan los cambios, se actualiza y se escribe la auditoría; si algo falla no queda nada
   * a medias.
   *
   * Auditoría, igual que Laravel:
   *  - `estudiante.actualizado` (lo genera EstudianteObserver::updated): siempre que cambie alguna columna, con la
   *    lista de columnas cambiadas + updated_at. Los observers de PHP no se disparan desde TypeScript: se reproduce.
   *  - `estudiante.editado` (lo escribe el controlador): SOLO si cambió algún campo sensible (cédula, nombres,
   *    apellidos, fecha de nacimiento, estado), con el detalle "antes → después".
   *
   * Un estudiante de otro colegio o borrado lógicamente → 404 (no se distingue de "no existe"). Si no cambia ninguna
   * columna no se escribe nada ni se audita. Cédula o matrícula repetidas dentro del colegio → 409.
   */
  async actualizar(id: number, entrada: ActualizarEstudiante, meta: MetaPeticion): Promise<EstudianteDto> {
    try {
      return await this.db.transaction().execute(async (trx) => {
        const actual = await trx
          .selectFrom('estudiantes')
          .select(['id', 'estado', 'sexo', ...NOMBRES_COLUMNAS_EDITABLES])
          .where('estudiantes.id', '=', id)
          .where('estudiantes.deleted_at', 'is', null)
          .forUpdate()
          .executeTakeFirst();
        if (!actual) throw new NotFoundException('Estudiante no encontrado.');

        // Valores actuales y propuestos por columna (todo como texto/null, que es como Laravel los compara).
        const antes: Record<string, string | null> = {};
        const despues: Record<string, string | null> = {};
        for (const [api, col] of COLUMNAS_EDITABLES) {
          const valorActual = (actual as unknown as Record<string, string | null>)[col] ?? null;
          antes[col] = valorActual;
          const propuesto = entrada[api];
          despues[col] = propuesto === undefined ? valorActual : (propuesto as string | null);
        }

        const cambiadas = camposCambiadosComoLaravel(antes, despues, NOMBRES_COLUMNAS_EDITABLES);
        if (cambiadas.length === 0) return aDto(actual);

        // Solo se escriben las columnas que cambian (como el update de Eloquent) + updated_at. Las claves salen de la
        // tabla COLUMNAS_EDITABLES, nunca del cliente.
        const aEscribir: Record<string, string | null> = { updated_at: ahoraUtc() };
        for (const col of cambiadas) if (col !== 'updated_at') aEscribir[col] = despues[col];
        await trx
          .updateTable('estudiantes')
          .set(aEscribir as never)
          .where('estudiantes.id', '=', id)
          .executeTakeFirstOrThrow();

        // Laravel escribe sus registros en este orden: primero el del observer (se dispara dentro de update()) y
        // después el del controlador.
        await this.auditoria.registrar(trx, {
          accion: 'estudiante.actualizado',
          modelo: MODELO_ESTUDIANTE,
          modeloId: id,
          descripcion: descripcionObserverActualizado(despues.apellidos!, despues.nombres!, cambiadas),
          ip: meta.ip,
          userAgent: meta.userAgent,
        });

        const sensibles = (valores: ValoresPorColumna): EstudianteAuditable => ({
          cedula: valores.cedula ?? null,
          nombres: valores.nombres ?? null,
          apellidos: valores.apellidos ?? null,
          fecha_nacimiento: valores.fecha_nacimiento ?? null,
          estado: valores.estado ?? null,
        });
        const cambiosSensibles = cambiosAuditados(sensibles(antes), sensibles(despues));
        if (cambiosSensibles.length > 0) {
          await this.auditoria.registrar(trx, {
            accion: 'estudiante.editado',
            modelo: MODELO_ESTUDIANTE,
            modeloId: id,
            descripcion: descripcionEdicionEstudiante(id, cambiosSensibles),
            ip: meta.ip,
            userAgent: meta.userAgent,
          });
        }

        return aDto({
          ...actual,
          numero_matricula: despues.numero_matricula!,
          cedula: despues.cedula,
          nombres: despues.nombres!,
          apellidos: despues.apellidos!,
          sexo: despues.sexo!,
          estado: despues.estado,
        });
      });
    } catch (e) {
      if (esDuplicado(e)) throw new ConflictException(mensajeDeDuplicado(e));
      throw e;
    }
  }
}
