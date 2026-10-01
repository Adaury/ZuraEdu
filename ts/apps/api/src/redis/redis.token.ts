/**
 * Cliente de Redis, o `null` si no hay REDIS_HOST (Redis es opcional).
 * Vive en su propio archivo para evitar un import circular entre `redis.module` y `laravel-cache`
 * (con el ciclo, el token llega `undefined` al decorador y Nest no resuelve la dependencia).
 */
export const REDIS = Symbol('REDIS');
