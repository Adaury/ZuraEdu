import { z } from 'zod';

/** Como `env()` de Laravel: una cadena vacía o el texto "null" (p. ej. `REDIS_PASSWORD=null`) significan "no definido". */
const opcionalComoLaravel = z
  .string()
  .optional()
  .transform((v) => (v === undefined || v.trim() === '' || v.trim().toLowerCase() === 'null' ? undefined : v));

/**
 * Variables de entorno validadas al arrancar: si falta o está mal una, la app NO inicia
 * (mejor fallar al desplegar que descubrirlo con el primer usuario). Es el equivalente a
 * `config/*.php`: el resto del código lee `Env`, nunca `process.env` directamente.
 */
const schema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),
  API_PORT: z.coerce.number().int().min(1).max(65535).default(3100),
  DATABASE_URL: z
    .string()
    .url()
    .refine((u) => u.startsWith('mysql://'), 'DATABASE_URL debe empezar por mysql://'),
  TENANCY_BASE_DOMAIN: z.string().min(1).default('zuraedu.com'),
  /**
   * Vida de la caché de sesión (token→usuario→tenant) y de permisos, en segundos. 0 la desactiva.
   * Es también el retraso MÁXIMO con que se nota en esta API un token revocado, un usuario
   * desactivado, un tenant suspendido o un permiso quitado en Laravel (no hay invalidación cruzada).
   */
  AUTH_CACHE_TTL_SECONDS: z.coerce.number().int().min(0).max(300).default(30),
  AUTH_CACHE_MAX_ENTRIES: z.coerce.number().int().min(1).max(100_000).default(5000),
  /** Sirve el contrato en GET /openapi.json. Es público (no contiene datos de colegios); ponlo en false para ocultarlo. */
  OPENAPI_ENABLED: z
    .enum(['true', 'false'])
    .default('true')
    .transform((v) => v === 'true'),

  // ── Redis (OPCIONAL). Mismos nombres y valores que el .env de Laravel: se copian tal cual. ─────────────
  // Sin REDIS_HOST la API funciona igual (solo /health informa "omitido"). Con REDIS_HOST, la API puede invalidar las
  // cachés de Laravel (misma instancia, base de datos y prefijos) y comprobar el estado de Redis en /health.
  REDIS_HOST: opcionalComoLaravel,
  REDIS_PORT: z.coerce.number().int().min(1).max(65535).default(6379),
  REDIS_USERNAME: opcionalComoLaravel,
  REDIS_PASSWORD: opcionalComoLaravel,
  /** Base de datos de la conexión `cache` de Laravel (config/database.php: REDIS_CACHE_DB, por defecto 1). */
  REDIS_CACHE_DB: z.coerce.number().int().min(0).max(255).default(1),
  /** Prefijo del CLIENTE Redis de Laravel (database.redis.options.prefix = REDIS_PREFIX). */
  REDIS_PREFIX: opcionalComoLaravel,
  /** Prefijo de la CACHÉ de Laravel (cache.prefix = CACHE_PREFIX). */
  CACHE_PREFIX: opcionalComoLaravel,
}).superRefine((env, ctx) => {
  // Si falta alguno de los dos prefijos, "invalidar" una clave apuntaría a una clave que no existe y la caché de Laravel
  // seguiría vieja sin que nadie lo notara. Por eso no tienen valor por defecto y son obligatorios con Redis.
  if (env.REDIS_HOST === undefined) return;
  for (const nombre of ['REDIS_PREFIX', 'CACHE_PREFIX'] as const) {
    if (!env[nombre]) {
      ctx.addIssue({
        code: 'custom',
        path: [nombre],
        message: `Obligatoria cuando REDIS_HOST está definido: copia el valor de ${nombre} del .env de Laravel (con APP_NAME largo el prefijo es largo y NO se puede adivinar).`,
      });
    }
  }
});

export type Env = z.infer<typeof schema>;

export const ENV = Symbol('ENV');

export function loadEnv(source: NodeJS.ProcessEnv = process.env): Env {
  const parsed = schema.safeParse(source);
  if (!parsed.success) {
    const detalle = parsed.error.issues.map((i) => `  - ${i.path.join('.')}: ${i.message}`).join('\n');
    throw new Error(`Configuración inválida (.env):\n${detalle}`);
  }
  return parsed.data;
}
