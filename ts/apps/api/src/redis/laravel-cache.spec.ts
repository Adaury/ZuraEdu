import type Redis from 'ioredis';
import { loadEnv } from '../config/env';
import { claveFisica, escaparGlob, LaravelCache, RedisNoConfiguradoError } from './laravel-cache';

const env = loadEnv({
  DATABASE_URL: 'mysql://u@h/db',
  REDIS_HOST: '127.0.0.1',
  REDIS_PREFIX: 'psac_database_',
  CACHE_PREFIX: 'psac_cache_',
});

describe('claveFisica', () => {
  it('apila prefijo de cliente + prefijo de caché + clave (formato real de Laravel)', () => {
    expect(claveFisica('psac_database_', 'psac_cache_', 't12_dashboard')).toBe('psac_database_psac_cache_t12_dashboard');
  });

  it('rechaza una clave vacía', () => {
    expect(() => claveFisica('a_', 'b_', '')).toThrow();
  });
});

describe('escaparGlob', () => {
  it('escapa los metacaracteres de SCAN MATCH para que el prefijo se busque literal', () => {
    expect(escaparGlob('a*b?c[d]e\\f')).toBe('a\\*b\\?c\\[d\\]e\\\\f');
    expect(escaparGlob('normal_123')).toBe('normal_123');
  });
});

describe('LaravelCache (con un Redis simulado)', () => {
  function simulado() {
    const del = jest.fn().mockResolvedValue(2);
    const scan = jest
      .fn()
      .mockResolvedValueOnce(['7', ['k1', 'k2']])
      .mockResolvedValueOnce(['0', ['k3']]);
    return { redis: { del, scan, exists: jest.fn().mockResolvedValue(1) } as unknown as Redis, del, scan };
  }

  it('olvidar borra la clave FÍSICA, no la lógica', async () => {
    const { redis, del } = simulado();
    await new LaravelCache(redis, env).olvidar('t1_a', 't1_b');
    expect(del).toHaveBeenCalledWith('psac_database_psac_cache_t1_a', 'psac_database_psac_cache_t1_b');
  });

  it('olvidarPorPrefijo recorre todo el cursor con SCAN y suma lo borrado', async () => {
    const { redis, del, scan } = simulado();
    del.mockResolvedValueOnce(2).mockResolvedValueOnce(1);
    expect(await new LaravelCache(redis, env).olvidarPorPrefijo('t1_dash')).toBe(3);
    expect(scan).toHaveBeenNthCalledWith(1, '0', 'MATCH', 'psac_database_psac_cache_t1_dash*', 'COUNT', 200);
    expect(scan).toHaveBeenNthCalledWith(2, '7', 'MATCH', 'psac_database_psac_cache_t1_dash*', 'COUNT', 200);
  });

  it('olvidarPorPrefijo no acepta un prefijo vacío (borraría toda la caché del sistema)', async () => {
    const { redis } = simulado();
    await expect(new LaravelCache(redis, env).olvidarPorPrefijo('')).rejects.toThrow(/vacío/);
  });

  it('sin Redis configurado, olvidar lanza (no finge) y olvidarMejorEsfuerzo devuelve false sin lanzar', async () => {
    const sinRedis = new LaravelCache(null, loadEnv({ DATABASE_URL: 'mysql://u@h/db' }));
    expect(sinRedis.habilitado).toBe(false);
    await expect(sinRedis.olvidar('x')).rejects.toBeInstanceOf(RedisNoConfiguradoError);
    await expect(sinRedis.olvidarMejorEsfuerzo('x')).resolves.toBe(false);
  });

  it('olvidarMejorEsfuerzo absorbe un fallo de Redis', async () => {
    const redis = { del: jest.fn().mockRejectedValue(new Error('timeout')) } as unknown as Redis;
    await expect(new LaravelCache(redis, env).olvidarMejorEsfuerzo('x')).resolves.toBe(false);
  });
});
