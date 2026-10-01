import { createHash, createHmac } from 'node:crypto';
import { Inject, Injectable, Logger, OnApplicationShutdown } from '@nestjs/common';
import { ENV, Env } from '../config/env';

export interface CredencialesReverb {
  appId: string;
  appKey: string;
  appSecret: string;
}

/**
 * Firma de la API HTTP de Pusher/Reverb (`POST /apps/{id}/events`):
 *   body_md5       = md5(cuerpo)
 *   string_to_sign = "POST\n{ruta}\nauth_key=..&auth_timestamp=..&auth_version=1.0&body_md5=.."   (parámetros en orden alfabético)
 *   auth_signature = HMAC-SHA256(secret, string_to_sign) en hexadecimal
 * Función pura (reloj y cuerpo como parámetros) para poder probarla con el vector oficial de Pusher.
 */
export function firmarPeticion(cred: CredencialesReverb, ruta: string, cuerpo: string, timestamp: number): Record<string, string> {
  const params: Record<string, string> = {
    auth_key: cred.appKey,
    auth_timestamp: String(timestamp),
    auth_version: '1.0',
    body_md5: createHash('md5').update(cuerpo).digest('hex'),
  };
  const aFirmar = `POST\n${ruta}\n${Object.keys(params).sort().map((k) => `${k}=${params[k]}`).join('&')}`;
  params.auth_signature = createHmac('sha256', cred.appSecret).update(aFirmar).digest('hex');
  return params;
}

/**
 * Nombre REAL del canal en el socket. `new PrivateChannel('tenant.1')` de Laravel se emite en `private-tenant.1` (y
 * `PresenceChannel` en `presence-…`): hay que añadir el prefijo igual que lo hace Laravel, o el evento no llega a nadie.
 * (El broadcasting de Laravel tuvo ya un bug de doble prefijo; ver la auditoría del 2026-09-03.)
 */
export const canalPrivado = (nombre: string): string => `private-${nombre}`;

/**
 * Publica eventos en Reverb desde TypeScript, equivalente a `Event::dispatch()` de un `ShouldBroadcastNow` de Laravel.
 * Solo EMITE: no abre conexiones WebSocket ni autoriza canales (eso lo hace Laravel en `/broadcasting/auth`).
 */
@Injectable()
export class ReverbPublisher implements OnApplicationShutdown {
  private readonly logger = new Logger(ReverbPublisher.name);
  private readonly pendientes = new Set<Promise<unknown>>();
  private readonly cred: CredencialesReverb | null;
  private readonly base: string;

  constructor(@Inject(ENV) env: Env) {
    this.cred =
      env.REVERB_APP_ID && env.REVERB_APP_KEY && env.REVERB_APP_SECRET
        ? { appId: env.REVERB_APP_ID, appKey: env.REVERB_APP_KEY, appSecret: env.REVERB_APP_SECRET }
        : null;
    this.base = `${env.REVERB_SCHEME}://${env.REVERB_HOST}:${env.REVERB_PORT}`;
  }

  get habilitado(): boolean {
    return this.cred !== null;
  }

  /**
   * Publica `evento` con `datos` en los canales dados (nombres LÓGICOS de Laravel, p. ej. `tenant.1`; se les antepone
   * `private-`). Lanza si Reverb no está configurado o rechaza la petición.
   */
  async publicar(canales: string[], evento: string, datos: unknown, ahora: () => number = () => Date.now()): Promise<void> {
    if (this.cred === null) throw new Error('Reverb no está configurado (REVERB_APP_ID/KEY/SECRET).');
    const ruta = `/apps/${this.cred.appId}/events`;
    // `data` viaja como TEXTO JSON dentro del JSON (así lo exige el protocolo Pusher).
    const cuerpo = JSON.stringify({ name: evento, channels: canales.map(canalPrivado), data: JSON.stringify(datos) });
    const query = new URLSearchParams(firmarPeticion(this.cred, ruta, cuerpo, Math.floor(ahora() / 1000)));

    const res = await fetch(`${this.base}${ruta}?${query.toString()}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: cuerpo,
      signal: AbortSignal.timeout(3000),
    });
    if (!res.ok) throw new Error(`Reverb respondió ${res.status} ${res.statusText}`);
  }

  /**
   * Emite SIN esperar (segundo plano): la petición HTTP no se retiene hasta 3 s si Reverb está lento o caído (en Laravel,
   * `ShouldBroadcastNow` sí bloquea). Los errores solo van al registro. Con `drenar()` se espera a que terminen.
   */
  emitir(canales: string[], evento: string, datos: unknown): void {
    const tarea = this.publicarMejorEsfuerzo(canales, evento, datos);
    this.pendientes.add(tarea);
    void tarea.finally(() => this.pendientes.delete(tarea));
  }

  /** Espera a que terminen las emisiones en segundo plano (pruebas y cierre ordenado). */
  async drenar(): Promise<void> {
    await Promise.allSettled([...this.pendientes]);
  }

  async onApplicationShutdown(): Promise<void> {
    await this.drenar();
  }

  /**
   * Igual que `publicar`, pero para usar DESPUÉS de confirmar una escritura (como el `try/catch` que rodea a
   * `DashboardActualizado::dispatch` en Laravel): un Reverb caído no debe convertir en error una operación ya guardada.
   */
  async publicarMejorEsfuerzo(canales: string[], evento: string, datos: unknown): Promise<boolean> {
    if (this.cred === null) return false;
    try {
      await this.publicar(canales, evento, datos);
      return true;
    } catch (e) {
      this.logger.warn(`No se pudo emitir "${evento}" a Reverb: ${(e as Error).message}`);
      return false;
    }
  }
}
