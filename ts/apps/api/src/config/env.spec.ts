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
