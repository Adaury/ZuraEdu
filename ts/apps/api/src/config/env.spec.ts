import { loadEnv } from './env';

describe('loadEnv', () => {
  it('aplica valores por defecto', () => {
    const env = loadEnv({ DATABASE_URL: 'mysql://root@127.0.0.1:3306/sge' });
    expect(env.API_PORT).toBe(3100);
    expect(env.NODE_ENV).toBe('development');
    expect(env.TENANCY_BASE_DOMAIN).toBe('zuraedu.com');
    expect(env.AUTH_CACHE_TTL_SECONDS).toBe(30);
    expect(env.AUTH_CACHE_MAX_ENTRIES).toBe(5000);
  });

  it('escucha por defecto solo en loopback (detrás de Nginx) y permite cambiarlo', () => {
    const base = { DATABASE_URL: 'mysql://u@h/db' };
    expect(loadEnv(base).API_HOST).toBe('127.0.0.1');
    expect(loadEnv({ ...base, API_HOST: '0.0.0.0' }).API_HOST).toBe('0.0.0.0');
    expect(() => loadEnv({ ...base, API_HOST: '' })).toThrow(/API_HOST/);
  });

  it('permite desactivar la caché de autenticación con 0', () => {
    expect(loadEnv({ DATABASE_URL: 'mysql://u@h/db', AUTH_CACHE_TTL_SECONDS: '0' }).AUTH_CACHE_TTL_SECONDS).toBe(0);
  });

  it('acota el TTL de la caché (un valor enorme dejaría permisos revocados por demasiado tiempo)', () => {
    const base = { DATABASE_URL: 'mysql://u@h/db' };
    expect(() => loadEnv({ ...base, AUTH_CACHE_TTL_SECONDS: '301' })).toThrow(/AUTH_CACHE_TTL_SECONDS/);
    expect(() => loadEnv({ ...base, AUTH_CACHE_TTL_SECONDS: '-1' })).toThrow(/AUTH_CACHE_TTL_SECONDS/);
    expect(() => loadEnv({ ...base, AUTH_CACHE_MAX_ENTRIES: '0' })).toThrow(/AUTH_CACHE_MAX_ENTRIES/);
  });

  it('convierte el puerto a número', () => {
    expect(loadEnv({ DATABASE_URL: 'mysql://u@h/db', API_PORT: '4000' }).API_PORT).toBe(4000);
  });

  describe('Redis (opcional, mismos nombres que el .env de Laravel)', () => {
    const base = { DATABASE_URL: 'mysql://u@h/db' };

    it('sin REDIS_HOST queda desactivado y no exige prefijos', () => {
      const env = loadEnv(base);
      expect(env.REDIS_HOST).toBeUndefined();
      expect(env.REDIS_PORT).toBe(6379);
      expect(env.REDIS_CACHE_DB).toBe(1);
    });

    it('trata "null" y la cadena vacía como no definidos (igual que env() de Laravel)', () => {
      const env = loadEnv({ ...base, REDIS_HOST: '127.0.0.1', REDIS_PASSWORD: 'null', REDIS_USERNAME: '', REDIS_PREFIX: 'a_', CACHE_PREFIX: 'b_' });
      expect(env.REDIS_PASSWORD).toBeUndefined();
      expect(env.REDIS_USERNAME).toBeUndefined();
    });

    it('con REDIS_HOST exige ambos prefijos (adivinarlos dejaría la caché de Laravel sin invalidar)', () => {
      expect(() => loadEnv({ ...base, REDIS_HOST: '127.0.0.1' })).toThrow(/REDIS_PREFIX[\s\S]*CACHE_PREFIX/);
      expect(() => loadEnv({ ...base, REDIS_HOST: '127.0.0.1', REDIS_PREFIX: 'a_' })).toThrow(/CACHE_PREFIX/);
      expect(() => loadEnv({ ...base, REDIS_HOST: '127.0.0.1', CACHE_PREFIX: 'b_' })).toThrow(/REDIS_PREFIX/);
    });

    it('conserva los prefijos tal cual (con guiones bajos finales)', () => {
      const env = loadEnv({ ...base, REDIS_HOST: 'h', REDIS_PREFIX: 'x_database_', CACHE_PREFIX: 'x_cache_' });
      expect(env.REDIS_PREFIX).toBe('x_database_');
      expect(env.CACHE_PREFIX).toBe('x_cache_');
    });
  });

  it('falla si falta DATABASE_URL', () => {
    expect(() => loadEnv({})).toThrow(/DATABASE_URL/);
  });

  it('falla si DATABASE_URL no es mysql://', () => {
    expect(() => loadEnv({ DATABASE_URL: 'postgres://u@h/db' })).toThrow(/mysql/);
  });

  it('falla con un puerto fuera de rango o un NODE_ENV desconocido', () => {
    expect(() => loadEnv({ DATABASE_URL: 'mysql://u@h/db', API_PORT: '70000' })).toThrow(/API_PORT/);
    expect(() => loadEnv({ DATABASE_URL: 'mysql://u@h/db', NODE_ENV: 'staging' })).toThrow(/NODE_ENV/);
  });
});
