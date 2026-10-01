import { z } from 'zod';

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
