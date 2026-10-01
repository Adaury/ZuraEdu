import { ConflictException, Inject, Injectable, Logger, NotFoundException } from '@nestjs/common';
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
import { ENV, Env } from '../config/env';
import { Database, DB } from '../db/db.module';
import { TenantContext } from '../tenancy/tenant-context';
import { COLUMNAS_EDITABLES, NOMBRES_COLUMNAS_EDITABLES } from './estudiante-columnas';
import { borrarFoto } from './foto-archivo';
import type { EstudiantesQuery } from './estudiantes.query';
import { consultaPagina, consultaTotal } from './estudiantes.queries';
import type { ActualizarEstudiante, CrearEstudiante } from './estudiantes.update';
import { patronNumerosDelAnio, siguienteNumeroMatricula } from './numero-matricula';

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
  private readonly logger = new Logger(EstudiantesService.name);

  constructor(
    @Inject(DB) private readonly db: Database,
    private readonly auditoria: AuditService,
    private readonly contexto: TenantContext,
    @Inject(ENV) private readonly env: Env,
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
   * Un estudiante por id. Lo necesita la pantalla de edición. Un estudiante de otro colegio o borrado lógicamente → 404
   * (no se distingue de "no existe"): el colegio sale del token y el id solo se usa como parámetro.
   */
  async obtener(id: number): Promise<EstudianteDto> {
    const fila = await this.db
      .selectFrom('estudiantes')
      .select(['estudiantes.id', 'estudiantes.numero_matricula', 'estudiantes.cedula', 'estudiantes.nombres', 'estudiantes.apellidos', 'estudiantes.sexo', 'estudiantes.estado'])
      .where('estudiantes.id', '=', id)
      .where('estudiantes.deleted_at', 'is', null)
      .executeTakeFirst();
    if (!fila) throw new NotFoundException('Estudiante no encontrado.');
    return aDto(fila);
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

  /**
   * Alta de un estudiante del colegio del usuario autenticado (el tenant sale del token, nunca del cuerpo).
   *
   * Auditoría igual que Laravel: `estudiante.creado` (lo genera EstudianteObserver::created) con
   * "Estudiante creado: Apellidos, Nombres (Matr: AAAA-NNNNN)". Alta y auditoría van en la misma transacción.
   *
   * Si no viene `numeroMatricula` se genera el siguiente del año (ver siguienteNumeroMatricula), serializando la
   * asignación por colegio con un bloqueo de fila: las altas simultáneas obtienen números consecutivos sin colisionar.
   * Por si otro sistema (Laravel calcula el suyo con count+1) toma el mismo número entre medias, la restricción única
   * lo detecta y se reintenta con el siguiente (hasta 5 veces). Un número dado por el cliente que ya exista, o una
   * cédula repetida en el colegio → 409.
   */
  async crear(entrada: CrearEstudiante, meta: MetaPeticion): Promise<EstudianteDto> {
    const generar = entrada.numeroMatricula === undefined;
    const maxIntentos = generar ? 5 : 1;

    for (let intento = 1; ; intento++) {
      try {
        return await this.db.transaction().execute(async (trx) => {
          const ahora = ahoraUtc();
          let numero = entrada.numeroMatricula;
          if (numero === undefined) {
            // Serializa la asignación de números POR COLEGIO: se bloquea la fila del tenant hasta confirmar la
            // transacción (se libera sola). Sin esto, N altas simultáneas calculan el mismo "máximo + 1" y solo una
            // gana; los reintentos no alcanzan con muchas a la vez y a un cliente que no envió número le llegaría un
            // 409 por una colisión interna. La tabla `tenants` no lleva filtro de tenant, así que se filtra por id.
            await trx
              .selectFrom('tenants')
              .select('id')
              .where('id', '=', this.contexto.tenantId)
              .forUpdate()
              .executeTakeFirstOrThrow();

            const anio = new Date().getUTCFullYear();
            // Sin filtrar deleted_at: la restricción única también cuenta a los borrados lógicamente.
            // (Orden lexicográfico: correcto mientras el sufijo tenga 5 dígitos, es decir, hasta 99.999 altas al año.)
            const ultimo = await trx
              .selectFrom('estudiantes')
              .select('numero_matricula')
              .where('numero_matricula', 'like', patronNumerosDelAnio(anio))
              .orderBy('numero_matricula', 'desc')
              .limit(1)
              .executeTakeFirst();
            numero = siguienteNumeroMatricula(anio, ultimo?.numero_matricula ?? null);
          }

          // Las claves salen de la tabla COLUMNAS_EDITABLES, nunca del cliente; tenant_id sale del token.
          const valores: Record<string, unknown> = {
            tenant_id: this.contexto.tenantId,
            numero_matricula: numero,
            created_at: ahora,
            updated_at: ahora,
          };
          for (const [api, col] of COLUMNAS_EDITABLES) {
            if (col === 'numero_matricula') continue;
            const valor = entrada[api];
            if (valor !== undefined) valores[col] = valor;
          }

          const resultado = await trx
            .insertInto('estudiantes')
            .values(valores as never)
            .executeTakeFirstOrThrow();
          const id = Number(resultado.insertId);

          await this.auditoria.registrar(trx, {
            accion: 'estudiante.creado',
            modelo: MODELO_ESTUDIANTE,
            modeloId: id,
            descripcion: `Estudiante creado: ${entrada.apellidos}, ${entrada.nombres} (Matr: ${numero})`,
            ip: meta.ip,
            userAgent: meta.userAgent,
          });

          return aDto({
            id,
            numero_matricula: numero,
            cedula: entrada.cedula ?? null,
            nombres: entrada.nombres,
            apellidos: entrada.apellidos,
            sexo: entrada.sexo,
            estado: entrada.estado,
          });
        });
      } catch (e) {
        if (esDuplicado(e)) {
          const esMatricula = mensajeDeDuplicado(e).includes('matrícula');
          if (generar && esMatricula && intento < maxIntentos) continue; // otra alta simultánea tomó ese número
          throw new ConflictException(mensajeDeDuplicado(e));
        }
        throw e;
      }
    }
  }

  /**
   * Borrado LÓGICO (como Laravel: SoftDeletes). Eloquent marca `deleted_at` y `updated_at` y dispara el evento
   * `deleted` (no el `updated`), así que se escribe solo `estudiante.eliminado` ("Estudiante eliminado: Apellidos,
   * Nombres"). Un estudiante de otro colegio o ya borrado → 404.
   *
   * Como Laravel, también borra el archivo de la foto del disco público (después de confirmar), pero SOLO si la API conoce esa
   * carpeta (`FOTOS_PUBLICAS_DIR`, mismo servidor que Laravel); si no, el archivo queda huérfano y se avisa en el registro. La
   * ruta guardada en la BD se valida: nunca se borra algo fuera de esa carpeta.
   */
  async eliminar(id: number, meta: MetaPeticion): Promise<void> {
    const foto = await this.db.transaction().execute(async (trx) => {
      const actual = await trx
        .selectFrom('estudiantes')
        .select(['id', 'nombres', 'apellidos', 'foto'])
        .where('estudiantes.id', '=', id)
        .where('estudiantes.deleted_at', 'is', null)
        .forUpdate()
        .executeTakeFirst();
      if (!actual) throw new NotFoundException('Estudiante no encontrado.');

      const ahora = ahoraUtc();
      await trx
        .updateTable('estudiantes')
        .set({ deleted_at: ahora, updated_at: ahora })
        .where('estudiantes.id', '=', id)
        .executeTakeFirstOrThrow();

      await this.auditoria.registrar(trx, {
        accion: 'estudiante.eliminado',
        modelo: MODELO_ESTUDIANTE,
        modeloId: id,
        descripcion: `Estudiante eliminado: ${actual.apellidos}, ${actual.nombres}`,
        ip: meta.ip,
        userAgent: meta.userAgent,
      });
      return actual.foto;
    });

    // Después de confirmar (nunca dentro de la transacción): como Laravel, se borra el archivo de la foto. La fila conserva la
    // ruta (borrado lógico), igual que en PHP. Si la API no conoce la carpeta de Laravel, el archivo queda huérfano.
    const r = await borrarFoto(this.env.FOTOS_PUBLICAS_DIR, foto, (m) => this.logger.warn(m));
    if (r === 'sin-configurar') {
      this.logger.warn(`El estudiante ${id} tenía foto pero FOTOS_PUBLICAS_DIR no está configurada: el archivo queda en el disco de Laravel.`);
    }
  }
}
