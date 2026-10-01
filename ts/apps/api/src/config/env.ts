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
