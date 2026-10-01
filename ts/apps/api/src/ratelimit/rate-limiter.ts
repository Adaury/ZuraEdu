import { Inject, Injectable, Logger, Optional } from '@nestjs/common';
import type Redis from 'ioredis';
import { RELOJ } from '../auth/auth-cache';
import { REDIS } from '../redis/redis.token';

export interface ResultadoLimite {
  permitido: boolean;
  limite: number;
  /** Peticiones que aún caben en la ventana actual (0 si ya se superó). */
  restante: number;
  /** Segundos hasta que la ventana se reinicia (mínimo 1). */
  reiniciaEnSegundos: number;
}

/** Prefijo propio de esta API: no puede chocar con las claves de la caché de Laravel (que llevan REDIS_PREFIX + CACHE_PREFIX). */
const PREFIJO_REDIS = 'tsapi:ratelimit:';

interface Ventana {
  cuenta: number;
  venceEn: number; // ms epoch
}

/**
 * Limitador de peticiones de ventana fija (igual que `Limit::perMinute()` de Laravel: la ventana empieza con la primera
 * petición de la clave y se reinicia al vencer).
 *
 *  - Con Redis configurado, el contador es COMPARTIDO entre todas las instancias de la API (SET NX EX + INCR en una
 *    transacción, atómico). Sin Redis, es por proceso: con N instancias el tope efectivo es N veces el configurado.
 *  - Si Redis falla, se usa la memoria de este proceso y se avisa UNA vez: es preferible limitar de forma aproximada
 *    que tumbar la API (o dejarla sin límite) porque Redis no responde.
 */
@Injectable()
export class RateLimiter {
  private readonly logger = new Logger(RateLimiter.name);
  private readonly memoria = new Map<string, Ventana>();
  private avisado = false;
  private readonly ahora: () => number;

  constructor(
    @Inject(REDIS) private readonly redis: Redis | null,
    @Optional() @Inject(RELOJ) reloj?: () => number,
  ) {
    this.ahora = reloj ?? (() => Date.now());
  }

  async registrar(clave: string, limite: number, ventanaSegundos = 60): Promise<ResultadoLimite> {
    if (this.redis !== null) {
      try {
        return await this.enRedis(this.redis, clave, limite, ventanaSegundos);
      } catch (e) {
        if (!this.avisado) {
          this.logger.warn(`Redis no disponible para limitar peticiones; se usa la memoria del proceso: ${(e as Error).message}`);
          this.avisado = true;
        }
      }
    }
    return this.enMemoria(clave, limite, ventanaSegundos);
  }

  private async enRedis(redis: Redis, clave: string, limite: number, ventanaSegundos: number): Promise<ResultadoLimite> {
    const k = PREFIJO_REDIS + clave;
    const r = await redis.multi().set(k, 0, 'EX', ventanaSegundos, 'NX').incr(k).ttl(k).exec();
    if (r === null) throw new Error('transacción abortada');
    for (const [err] of r) if (err) throw err;
    const cuenta = Number(r[1][1]);
    const ttl = Number(r[2][1]);
    this.avisado = false;
    return this.resultado(cuenta, limite, ttl > 0 ? ttl : ventanaSegundos);
  }

  private enMemoria(clave: string, limite: number, ventanaSegundos: number): ResultadoLimite {
    const t = this.ahora();
    let v = this.memoria.get(clave);
    if (!v || v.venceEn <= t) {
      v = { cuenta: 0, venceEn: t + ventanaSegundos * 1000 };
      this.memoria.set(clave, v);
      if (this.memoria.size > 10_000) this.purgar(t);
    }
    v.cuenta++;
    return this.resultado(v.cuenta, limite, Math.max(1, Math.ceil((v.venceEn - t) / 1000)));
  }

  /** Quita las ventanas vencidas para que el mapa no crezca sin límite (una clave por usuario). */
  private purgar(t: number): void {
    for (const [k, v] of this.memoria) if (v.venceEn <= t) this.memoria.delete(k);
  }

  private resultado(cuenta: number, limite: number, reinicia: number): ResultadoLimite {
    return {
      permitido: cuenta <= limite,
      limite,
      restante: Math.max(0, limite - cuenta),
      reiniciaEnSegundos: Math.max(1, reinicia),
    };
  }
}
