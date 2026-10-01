import { Injectable } from '@nestjs/common';
import type { Kysely } from 'kysely';
import type { DB as Schema } from '../db/db';
import { TenantContext } from '../tenancy/tenant-context';

export interface EntradaAuditoria {
  /** Convención de Laravel: 'estudiante.editado', 'pago.anulado'... */
  accion: string;
  /** Nombre de clase del modelo de Laravel, p. ej. 'App\\Models\\Estudiante' (la pantalla actual lo muestra). */
  modelo: string;
  modeloId: number;
  descripcion: string;
  ip?: string | null;
  userAgent?: string | null;
}

/** Fecha/hora UTC en el formato de Laravel ('Y-m-d H:i:s'; config/app.php timezone = UTC). */
export function ahoraUtc(): string {
  return new Date().toISOString().slice(0, 19).replace('T', ' ');
}

/**
 * Escribe en `activity_logs` con el MISMO formato que `ActivityLog::registrar()` de Laravel, para que la
 * pantalla de auditoría actual muestre juntos los cambios hechos desde PHP y desde TypeScript.
 *
 * Recibe la conexión como parámetro a propósito: se llama con la TRANSACCIÓN de la operación, de modo que
 * el cambio y su registro de auditoría se confirman (o se deshacen) juntos. tenant_id y user_id salen del
 * contexto de la petición, nunca de los argumentos.
 */
@Injectable()
export class AuditService {
  constructor(private readonly contexto: TenantContext) {}

  async registrar(db: Kysely<Schema>, e: EntradaAuditoria): Promise<void> {
    await db
      .insertInto('activity_logs')
      .values({
        tenant_id: this.contexto.tenantId,
        user_id: this.contexto.userId,
        accion: e.accion.slice(0, 100),
        modelo: e.modelo.slice(0, 100),
        modelo_id: e.modeloId,
        descripcion: e.descripcion,
        ip: e.ip ? e.ip.slice(0, 45) : null,
        user_agent: e.userAgent ? e.userAgent.slice(0, 255) : null,
        created_at: ahoraUtc(),
      })
      .execute();
  }
}
