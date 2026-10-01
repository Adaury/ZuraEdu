import { Inject, Injectable, Logger, OnApplicationShutdown } from '@nestjs/common';
import { ahoraUtc } from '../audit/audit.service';
import { ENV, Env } from '../config/env';
import { Database, DB } from '../db/db.module';
import { LaravelCache } from '../redis/laravel-cache';
import { ReverbPublisher } from '../realtime/reverb.publisher';
import { TenantContext } from '../tenancy/tenant-context';
import { CATEGORIAS_INAPP_BLOQUEADAS, categoriaDe, iconoDe } from './categorias';

export interface ResultadoEnvio {
  /** Se creó la fila in-app. `false` si la institución apagó la categoría o el destinatario no es del colegio. */
  creada: boolean;
  /** Hubo push y/o evento en tiempo real en segundo plano (no implica que lleguen). */
  segundoPlano: boolean;
}

/**
 * Notificaciones desde TypeScript, equivalente a `Notificacion::enviar()` de Laravel (rama síncrona, que es también lo que
 * hace `EnviarNotificacionJob` en el worker):
 *
 *   1. Gate in-app de la INSTITUCIÓN: setting `notif_inapp_{categoría}` (≠ '0'); la categoría 'sistema' no se puede apagar.
 *   2. Fila en `notificaciones` + borrado de la caché `user_{id}_notif_unread` de Laravel.
 *   3. Push a Expo si la institución (`notif_push_{categoría}` ≠ '0') Y el usuario (`notif_push_prefs[categoría]` ≠ false) lo permiten.
 *   4. Evento `notification.created` por Reverb al canal `private-user.{id}`.
 *
 * Diferencias deliberadas respecto a PHP:
 *  - Los pasos 3 y 4 (red) corren EN SEGUNDO PLANO: Expo puede tardar hasta 8 s y no debe retener la respuesta HTTP (PHP evita
 *    esto con la cola). Los pasos 1 y 2 (BD) se esperan, para que la fila exista cuando se responde.
 *  - El destinatario se valida contra la BD (debe ser un usuario de ESTE colegio) antes de insertar o tocar sus tokens: el id
 *    puede venir de datos, pero nunca se confía a ciegas (`device_tokens` no tiene `tenant_id`).
 *  - Las horas del evento se dan en UTC (`config/app.php` timezone = UTC), como `now()->format('H:i')` en Laravel.
 */
@Injectable()
export class NotificacionesService implements OnApplicationShutdown {
  private readonly logger = new Logger(NotificacionesService.name);
  private readonly pendientes = new Set<Promise<void>>();
  private readonly expoUrl: string;

  constructor(
    @Inject(DB) private readonly db: Database,
    private readonly contexto: TenantContext,
    private readonly cache: LaravelCache,
    private readonly reverb: ReverbPublisher,
    @Inject(ENV) env: Env,
  ) {
    this.expoUrl = env.EXPO_PUSH_URL;
  }

  async enviar(userId: number, tipo: string, titulo: string, mensaje: string, datos: Record<string, unknown> = {}): Promise<ResultadoEnvio> {
    const tenantId = this.contexto.tenantId;
    const categoria = categoriaDe(tipo);

    // Gate 1 y 2 de la institución: una sola lectura de los dos settings de la categoría.
    const filas = await this.db
      .selectFrom('system_settings')
      .select(['key', 'value'])
      .where('key', 'in', [`notif_inapp_${categoria}`, `notif_push_${categoria}`])
      .execute();
    const valor = (clave: string) => filas.find((f) => f.key === clave)?.value ?? '1'; // sin fila = activo ('1')
    const inAppActivo = CATEGORIAS_INAPP_BLOQUEADAS.has(categoria) || valor(`notif_inapp_${categoria}`) !== '0';
    if (!inAppActivo) return { creada: false, segundoPlano: false };
    const pushInstitucion = valor(`notif_push_${categoria}`) !== '0';

    // El destinatario debe ser de este colegio (la consulta lleva tenant_id por el plugin).
    const usuario = await this.db.selectFrom('users').select(['id', 'notif_push_prefs']).where('id', '=', userId).executeTakeFirst();
    if (!usuario) {
      this.logger.warn(`Notificación "${tipo}" omitida: el usuario ${userId} no existe en el colegio ${tenantId}.`);
      return { creada: false, segundoPlano: false };
    }

    const ahora = ahoraUtc();
    const tieneDatos = Object.keys(datos).length > 0; // `$datos ?: null` en PHP: un arreglo vacío se guarda como NULL
    await this.db
      .insertInto('notificaciones')
      .values({
        tenant_id: tenantId,
        user_id: userId,
        tipo,
        titulo,
        mensaje,
        datos: tieneDatos ? JSON.stringify(datos) : null,
        leida: 0,
        created_at: ahora,
        updated_at: ahora,
      })
      .execute();
    await this.cache.olvidarMejorEsfuerzo(`user_${userId}_notif_unread`);

    const prefs = parsearPrefs(usuario.notif_push_prefs);
    const hacerPush = pushInstitucion && prefs[categoria] !== false;
    const tokens = hacerPush ? await this.tokensExpo(userId) : [];

    const url = typeof datos.url === 'string' ? datos.url : null;
    const tareas: Promise<void>[] = [];
    if (tokens.length > 0) tareas.push(this.enviarPush(tokens, titulo, mensaje, { ...datos, tipo }));
    if (this.reverb.habilitado) {
      tareas.push(
        this.reverb
          .publicarMejorEsfuerzo([`user.${userId}`], 'notification.created', {
            tipo,
            titulo,
            mensaje,
            url,
            icono: iconoDe(tipo),
            tiempo: ahora.slice(11, 16), // 'HH:mm' en UTC
          })
          .then(() => undefined),
      );
    }
    for (const t of tareas) this.rastrear(t);
    return { creada: true, segundoPlano: tareas.length > 0 };
  }

  /** Espera a que terminen los envíos en segundo plano (pruebas y cierre ordenado). */
  async drenar(): Promise<void> {
    await Promise.allSettled([...this.pendientes]);
  }

  async onApplicationShutdown(): Promise<void> {
    await this.drenar();
  }

  private rastrear(tarea: Promise<void>): void {
    this.pendientes.add(tarea);
    void tarea.finally(() => this.pendientes.delete(tarea));
  }

  /** Solo tokens de Expo válidos, como `PushNotificationService::dispatch`. */
  private async tokensExpo(userId: number): Promise<string[]> {
    const filas = await this.db.selectFrom('device_tokens').select('token').where('user_id', '=', userId).execute();
    return filas.map((f) => f.token).filter((t) => t.startsWith('ExponentPushToken'));
  }

  /** Lotes de 100, 8 s de espera, errores solo al registro (igual que PHP: un push fallido nunca rompe nada). */
  private async enviarPush(tokens: string[], titulo: string, cuerpo: string, datos: Record<string, unknown>): Promise<void> {
    for (let i = 0; i < tokens.length; i += 100) {
      const mensajes = tokens.slice(i, i + 100).map((to) => ({ to, title: titulo, body: cuerpo, data: datos, sound: 'default' }));
      try {
        await fetch(this.expoUrl, {
          method: 'POST',
          headers: { Accept: 'application/json', 'Accept-Encoding': 'gzip, deflate', 'Content-Type': 'application/json' },
          body: JSON.stringify(mensajes),
          signal: AbortSignal.timeout(8000),
        });
      } catch (e) {
        this.logger.warn(`Push a Expo falló: ${(e as Error).message}`);
      }
    }
  }
}

/** `notif_push_prefs` puede venir como objeto o como texto JSON (según el driver); nunca debe lanzar. */
function parsearPrefs(raw: unknown): Record<string, unknown> {
  if (raw && typeof raw === 'object') return raw as Record<string, unknown>;
  if (typeof raw === 'string' && raw.trim() !== '') {
    try {
      const v: unknown = JSON.parse(raw);
      return v && typeof v === 'object' ? (v as Record<string, unknown>) : {};
    } catch {
      return {};
    }
  }
  return {};
}
