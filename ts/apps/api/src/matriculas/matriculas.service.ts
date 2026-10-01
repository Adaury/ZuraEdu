import { ConflictException, Inject, Injectable, Logger, UnprocessableEntityException } from '@nestjs/common';
import type { MatriculaDto } from '@zuraedu/shared';
import { ahoraUtc, AuditService } from '../audit/audit.service';
import { Database, DB } from '../db/db.module';
import { NotificacionesService } from '../notificaciones/notificaciones.service';
import { ReverbPublisher } from '../realtime/reverb.publisher';
import { TenantContext } from '../tenancy/tenant-context';
import type { CrearMatricula } from './matriculas.input';

export interface MetaPeticion {
  ip: string | null;
  userAgent: string | null;
}

/** Nombre de clase del modelo tal como lo guarda Laravel en `activity_logs.modelo` (`Matricula::class`). */
export const MODELO_MATRICULA = 'App\\Models\\Matricula';

const referenciaInvalida = (campo: string, mensaje: string) =>
  new UnprocessableEntityException({ message: 'Referencia inválida.', errors: [{ campo, mensaje }] });

/** Error 1062 de MySQL (clave única duplicada), venga como `errno` o como código. */
const esDuplicado = (e: unknown): boolean => {
  const x = e as { errno?: number; code?: string };
  return x?.errno === 1062 || x?.code === 'ER_DUP_ENTRY';
};

/**
 * Matricular a un estudiante (`MatriculaController@store` de Laravel), con sus efectos:
 *
 *  1. Dentro de UNA transacción: se bloquea el grupo (`for update`), se comprueba el cupo, se calcula `numero_orden` y se crea la
 *     matrícula en estado `activa`. El bloqueo serializa matrículas simultáneas al mismo grupo (sin él, dos peticiones podrían
 *     llenar el último cupo a la vez o repetir el número de orden).
 *  2. Después de confirmar (nunca dentro): evento `dashboard.updated` en `private-tenant.{id}` y notificación «Matrícula
 *     confirmada» al estudiante y a sus representantes. Ninguno puede convertir en error una matrícula ya guardada.
 *
 * Más estricto que Laravel, a propósito (en Laravel `exists:tabla,id` NO filtra por colegio ni por borrado lógico):
 *  - el año escolar, el estudiante y el grupo deben existir EN ESTE COLEGIO y no estar borrados lógicamente;
 *  - el grupo debe ser del año escolar indicado;
 *  - se deja auditoría `matricula.creada` (Laravel no audita el alta, y `matriculas` no guarda quién matriculó).
 *
 * Códigos: 422 referencia inexistente / grupo de otro año; 409 ya matriculado en ese año o sin cupo.
 */
@Injectable()
export class MatriculasService {
  private readonly logger = new Logger(MatriculasService.name);

  constructor(
    @Inject(DB) private readonly db: Database,
    private readonly contexto: TenantContext,
    private readonly auditoria: AuditService,
    private readonly reverb: ReverbPublisher,
    private readonly notificaciones: NotificacionesService,
  ) {}

  async crear(entrada: CrearMatricula, meta: MetaPeticion): Promise<MatriculaDto> {
    let resultado;
    try {
      resultado = await this.db.transaction().execute(async (trx) => {
        // IMPORTANTE: el bloqueo del grupo tiene que ser la PRIMERA sentencia de la transacción. MySQL (REPEATABLE READ) fija la
        // instantánea en la primera lectura NORMAL; si se leyera antes cualquier otra tabla, al conseguir el bloqueo los `count`
        // de abajo seguirían viendo la instantánea vieja y no las matrículas que otra transacción acaba de confirmar: todas las
        // peticiones simultáneas verían el mismo cupo libre y el mismo `numero_orden`. (Así lo cazó la prueba de concurrencia.)
        // Se bloquea SOLO la fila del grupo (sin joins: bloquearía también grados y secciones).
        const grupo = await trx
          .selectFrom('grupos')
          .select(['id', 'school_year_id', 'capacidad', 'grado_id', 'seccion_id'])
          .where('id', '=', entrada.grupoId)
          .where('deleted_at', 'is', null)
          .forUpdate()
          .executeTakeFirst();
        if (!grupo) throw referenciaInvalida('grupoId', 'El grupo no existe en este colegio.');

        const anio = await trx.selectFrom('school_years').select('id').where('id', '=', entrada.schoolYearId).executeTakeFirst();
        if (!anio) throw referenciaInvalida('schoolYearId', 'El año escolar no existe en este colegio.');
        if (grupo.school_year_id !== entrada.schoolYearId) {
          throw referenciaInvalida('grupoId', 'El grupo no pertenece al año escolar indicado.');
        }

        const estudiante = await trx
          .selectFrom('estudiantes')
          .select(['id', 'nombres', 'apellidos', 'user_id'])
          .where('id', '=', entrada.estudianteId)
          .where('deleted_at', 'is', null)
          .executeTakeFirst();
        if (!estudiante) throw referenciaInvalida('estudianteId', 'El estudiante no existe en este colegio.');

        const yaMatriculado = await trx
          .selectFrom('matriculas')
          .select('id')
          .where('school_year_id', '=', entrada.schoolYearId)
          .where('estudiante_id', '=', entrada.estudianteId)
          .executeTakeFirst();
        if (yaMatriculado) throw new ConflictException('Este estudiante ya está matriculado en este año escolar.');

        const nombres = await trx
          .selectFrom('grados')
          .select('nombre')
          .where('id', '=', grupo.grado_id)
          .executeTakeFirst()
          .then(async (g) => ({
            grado: g?.nombre ?? '',
            seccion: (await trx.selectFrom('secciones').select('nombre').where('id', '=', grupo.seccion_id).executeTakeFirst())?.nombre ?? '',
          }));
        const nombreGrupo = `${nombres.grado} ${nombres.seccion}`;

        const { ocupados } = await trx
          .selectFrom('matriculas')
          .select((eb) => eb.fn.countAll<number>().as('ocupados'))
          .where('grupo_id', '=', grupo.id)
          .where('estado', '=', 'activa')
          .executeTakeFirstOrThrow();
        const enUso = Number(ocupados);
        if (enUso + 1 > grupo.capacidad) {
          const disponibles = Math.max(0, grupo.capacidad - enUso);
          throw new ConflictException(
            `El grupo ${nombreGrupo} no tiene cupo suficiente: ${enUso}/${grupo.capacidad} ocupados, quedan ${disponibles} disponible(s).`,
          );
        }

        // Cuenta TODAS las matrículas del grupo (también retiradas, etc.), igual que Laravel.
        const { total } = await trx
          .selectFrom('matriculas')
          .select((eb) => eb.fn.countAll<number>().as('total'))
          .where('grupo_id', '=', grupo.id)
          .executeTakeFirstOrThrow();
        const numeroOrden = Number(total) + 1;

        const ahora = ahoraUtc();
        const insercion = await trx
          .insertInto('matriculas')
          .values({
            tenant_id: this.contexto.tenantId,
            school_year_id: entrada.schoolYearId,
            estudiante_id: entrada.estudianteId,
            grupo_id: grupo.id,
            fecha_matricula: entrada.fechaMatricula,
            numero_orden: numeroOrden,
            estado: 'activa',
            observaciones: entrada.observaciones,
            created_at: ahora,
            updated_at: ahora,
          })
          .executeTakeFirstOrThrow();
        const id = Number(insercion.insertId);

        await this.auditoria.registrar(trx, {
          accion: 'matricula.creada',
          modelo: MODELO_MATRICULA,
          modeloId: id,
          descripcion: `Matrícula creada: ${estudiante.apellidos}, ${estudiante.nombres} en ${nombreGrupo} (orden ${numeroOrden}, año escolar #${entrada.schoolYearId})`,
          ip: meta.ip,
          userAgent: meta.userAgent,
        });

        return { id, numeroOrden, estudiante, nombreGrupo };
      });
    } catch (e) {
      // Dos peticiones simultáneas para el mismo estudiante y año: la restricción única de la BD decide cuál gana.
      if (esDuplicado(e)) throw new ConflictException('Este estudiante ya está matriculado en este año escolar.');
      throw e;
    }

    // Efectos posteriores a la confirmación: nada de esto puede deshacer ni fallar la matrícula.
    this.reverb.emitir([`tenant.${this.contexto.tenantId}`], 'dashboard.updated', {
      tipo: 'nueva_matricula',
      datos: { grupo_id: entrada.grupoId },
    });
    await this.notificarMatricula(entrada.estudianteId, resultado.estudiante, resultado.nombreGrupo);

    return {
      id: resultado.id,
      schoolYearId: entrada.schoolYearId,
      estudianteId: entrada.estudianteId,
      grupoId: entrada.grupoId,
      fechaMatricula: entrada.fechaMatricula,
      numeroOrden: resultado.numeroOrden,
      estado: 'activa',
      observaciones: entrada.observaciones,
    };
  }

  /** «✅ Matrícula confirmada» al usuario del estudiante (si lo tiene) y de cada representante. Un fallo no afecta a los demás. */
  private async notificarMatricula(
    estudianteId: number,
    estudiante: { nombres: string; apellidos: string; user_id: number | null },
    nombreGrupo: string,
  ): Promise<void> {
    const titulo = '✅ Matrícula confirmada';
    const mensaje = `${estudiante.apellidos}, ${estudiante.nombres} ha sido matriculado/a en ${nombreGrupo} para el año escolar en curso.`;
    try {
      const reps = await this.db
        .selectFrom('estudiante_representante as er')
        .innerJoin('representantes as r', 'r.id', 'er.representante_id') // `r` lleva el filtro de colegio
        .select('r.user_id')
        .where('er.estudiante_id', '=', estudianteId)
        .execute();
      const destinatarios = [estudiante.user_id, ...reps.map((r) => r.user_id)].filter((u): u is number => !!u);
      for (const userId of destinatarios) {
        try {
          await this.notificaciones.enviar(userId, 'general', titulo, mensaje);
        } catch (e) {
          this.logger.warn(`No se pudo notificar la matrícula al usuario ${userId}: ${(e as Error).message}`);
        }
      }
    } catch (e) {
      this.logger.warn(`No se pudo notificar la matrícula del estudiante ${estudianteId}: ${(e as Error).message}`);
    }
  }
}
