import { Inject, Injectable, Logger } from '@nestjs/common';
import type Redis from 'ioredis';
import { ENV, Env } from '../config/env';
import { REDIS } from './redis.token';

/**
 * Clave FÍSICA que Laravel usa en Redis para un elemento de su caché. Verificada con el Laravel real
 * (Cache::put + redis-cli):
 *
 *     {REDIS_PREFIX}{CACHE_PREFIX}{clave}      en la base de datos REDIS_CACHE_DB (por defecto 1)
 *
 * Son DOS prefijos apilados: el del cliente Redis (config/database.php `options.prefix`) y el de la caché
 * (config/cache.php `prefix`). Con un APP_NAME largo la clave real mide más de 100 caracteres; si se usara solo la
 * clave lógica, "invalidar" no tocaría nada y la caché de Laravel seguiría vieja sin que nadie lo notara.
 */
export function claveFisica(redisPrefix: string, cachePrefix: string, clave: string): string {
  if (clave === '') throw new Error('La clave de caché no puede estar vacía.');
  return `${redisPrefix}${cachePrefix}${clave}`;
}

/** Escapa los caracteres con significado en un patrón glob de Redis (`* ? [ ] \`), para buscar un prefijo literal. */
export function escaparGlob(texto: string): string {
  return texto.replace(/[\\*?[\]]/g, (c) => `\\${c}`);
}

/** Se lanza al invalidar una caché de Laravel sin que Redis esté configurado: nunca se finge que se invalidó. */
export class RedisNoConfiguradoError extends Error {
  constructor() {
    super('Redis no está configurado (REDIS_HOST): no se pueden invalidar las cachés de Laravel.');
    this.name = 'RedisNoConfiguradoError';
  }
}

/**
 * Invalida elementos de la caché de LARAVEL desde TypeScript (misma instancia de Redis, misma base de datos y mismos
 * prefijos). Sirve para que las escrituras migradas dejen las cachés de PHP (dashboards, ranking, etc.) igual que
 * las dejaría el controlador de Laravel. Solo borra: no lee ni escribe valores (Laravel los guarda serializados con
 * `serialize()` de PHP y no se interpretan desde aquí).
 *
 * Las claves son SIEMPRE las lógicas de Laravel (`t12_dashboard_chart_3`); el prefijo se añade aquí.
 */
@Injectable()
export class LaravelCache {
  private readonly logger = new Logger(LaravelCache.name);
  private readonly redisPrefix: string;
  private readonly cachePrefix: string;

  constructor(
    @Inject(REDIS) private readonly redis: Redis | null,
    @Inject(ENV) env: Env,
  ) {
    this.redisPrefix = env.REDIS_PREFIX ?? '';
    this.cachePrefix = env.CACHE_PREFIX ?? '';
  }

  get habilitado(): boolean {
    return this.redis !== null;
  }

  private cliente(): Redis {
    if (this.redis === null) throw new RedisNoConfiguradoError();
    return this.redis;
  }

  /** Borra claves exactas. Devuelve cuántas existían. Lanza si Redis no está configurado o falla. */
  async olvidar(...claves: string[]): Promise<number> {
    const redis = this.cliente();
    if (claves.length === 0) return 0;
    return redis.del(...claves.map((c) => claveFisica(this.redisPrefix, this.cachePrefix, c)));
  }

  /**
   * Igual que `olvidar`, pero para usar DESPUÉS de confirmar una escritura: el dato ya está guardado, así que si Redis
   * falla o no está configurado no se debe devolver un error al cliente. Se registra una advertencia y se devuelve false.
   */
  async olvidarMejorEsfuerzo(...claves: string[]): Promise<boolean> {
    try {
      await this.olvidar(...claves);
      return true;
    } catch (e) {
      this.logger.warn(`No se pudo invalidar la caché de Laravel (${claves.join(', ')}): ${(e as Error).message}`);
      return false;
    }
  }

  /**
   * Borra todas las claves cuya clave LÓGICA empiece por `prefijoLogico` (p. ej. `t12_dashboard_`). Usa SCAN (no KEYS: no
   * bloquea Redis). Devuelve cuántas borró.
   */
  async olvidarPorPrefijo(prefijoLogico: string): Promise<number> {
    const redis = this.cliente();
    if (prefijoLogico === '') throw new Error('El prefijo de caché no puede estar vacío (borraría todo).');

    const patron = `${escaparGlob(claveFisica(this.redisPrefix, this.cachePrefix, prefijoLogico))}*`;
    let borradas = 0;
    let cursor = '0';
    do {
      const [siguiente, claves] = await redis.scan(cursor, 'MATCH', patron, 'COUNT', 200);
      cursor = siguiente;
      if (claves.length > 0) borradas += await redis.del(...claves);
    } while (cursor !== '0');
    return borradas;
  }

  /** ¿Existe la clave en la caché de Laravel? (diagnóstico y pruebas). */
  async existe(clave: string): Promise<boolean> {
    return (await this.cliente().exists(claveFisica(this.redisPrefix, this.cachePrefix, clave))) === 1;
  }
}
